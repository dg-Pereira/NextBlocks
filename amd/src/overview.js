/**
 *
 * @module      mod_nextblocks/overview
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (activityId) => {
    const rows = document.querySelectorAll('.user-submission-url');
    for (const row of rows) {
        // eslint-disable-next-line no-unused-vars
        const userId = row.getAttribute('id').split("=")[1];


        //change row href to report.php
        row.href = `report.php?id=${activityId}&userid=${userId}`;

        //window.location.replace("../report.php?id=" + id + "&userid=" + userid);
    }
};