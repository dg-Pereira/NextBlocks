<?php
class Chat_Server {

    private $address;
    private $port;
    private $null;

    function __construct() {
        $this->address = "0.0.0.0";
        $this->port = 8060;
        $this->null = null;
    }

    /**
     * Initializes the chat server.
     *
     * This method sets up a WebSocket server that listens for incoming connections and messages.
     * It runs in an infinite loop, so it will keep the server running indefinitely.
     * If the server encounters an error (e.g., if socket_select() returns false), it will restart the server.
     */
    public function init_chat_server() {
        // Outer loop to restart the server if there is an error
        while (true) {
            // Create new TCP socket
            $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            // Bind socket to specified address and port
            socket_bind($sock, $this->address, $this->port);
            // Listen for incoming connections on socket
            socket_listen($sock);

            // Holds the main socket and any client sockets
            $connections = [$sock];

            // Inner loop to handle incoming connections and messages
            while (true) {
                // Arrays for socket_select()
                $reads = $connections;
                $writes = $exceptions = $this->null;

                // Wait for change in status of any socket in the $reads array
                $changed_sockets = socket_select($reads, $writes, $exceptions, 0);

                // If socket_select() returns false, an error occurred
                if ($changed_sockets === false) {
                    // Print the error message and exit the inner loop to restart the server
                    echo "socket_select() failed, reason: " . socket_strerror(socket_last_error()) . "\n";
                    break;
                }

                // If the main socket is ready to read, a new connection is incoming
                if (in_array($sock, $reads)) {
                    // Accept new connection
                    $new_connection = socket_accept($sock);
                    // Read initial request from client
                    $header = socket_read($new_connection, 1024);
                    // Perform WebSocket handshake
                    $this->handshake($header, $new_connection);

                    // Add new connection to connection array
                    $connections[] = $new_connection;

                    // Send message to new client
                    $reply = "new connection\n";
                    $reply = $this->pack_data($reply);
                    socket_write($new_connection, $reply, strlen($reply));

                    // Remove main socket from the $reads array for this iteration
                    $sock_index = array_search($sock, $reads);
                    unset($reads[$sock_index]);
                }

                // Handle incoming messages from clients
                foreach ($reads as $key => $value) {
                    // Read message from the client
                    $data = socket_read($value, 1024);

                    // If client sent a message
                    if (!empty($data)) {
                        // Unmask message
                        $message = $this->unmask($data);
                        // Pack message for sending
                        $packed_message = $this->pack_data($message);

                        // Send message to all connected clients
                        foreach ($connections as $ckey => $cvalue) {
                            // Skip main socket
                            if ($ckey === 0) {
                                continue;
                            }
                            socket_write($cvalue, $packed_message, strlen($packed_message));
                        }
                    } else if ($data === '') { // If client closed the connection
                        echo "disconnecting client $key\n";
                        // Remove client from the array of connections
                        unset($connections[$key]);
                        // Close client socket
                        socket_close($value);
                    }
                }
            }

            // Close main socket before restarting server
            socket_close($sock);
            echo "Restarting server...\n";
        }
    }

    private function unmask($text): string {
        if (strlen($text) < 2) {
            // Handle error: $text is too short
            return "";
        }
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } else if ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";

        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }

        return $text;
    }

    private function pack_data($text): string {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }

        return $header.$text;
    }

    private function handshake($request_header, $sock) {
        $headers = [];
        $lines = preg_split("/\r\n/", $request_header);

        foreach ($lines as $line) {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        if (!isset($headers['Sec-WebSocket-Key'])) {
            // Handle error: 'Sec-WebSocket-Key' header not present
            $response_header = "HTTP/1.1 400 Bad Request\r\n\r\n";
            socket_write($sock, $response_header, strlen($response_header));
            return;
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $response_header = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $secAccept\r\n\r\n";

        socket_write($sock, $response_header, strlen($response_header));
    }
}

$chat = new Chat_Server();
$chat->init_chat_server();
