/**
 * NED FN Tabs Format
 *
 * @package    course/format
 * @subpackage fntabs
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  2017 Gareth J Barnard
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {

    "use strict"; // jshint ;_;
    log.debug('NED FN Tabs AMD tabsettings');
    return {
        init: function() {
            $(document).ready(function($) {
                $('#id_managecolorschemas').click(function() {
                    var colorschema = $('#id_colorschema option:selected').val();
                    var courseid = $("[name='id']").val();
                    location.href = M.cfg.wwwroot + '/course/format/fntabs/colorschema_edit.php?courseid=' + courseid + '&edit=' + colorschema;
                });
            });
            log.debug('NED FN Tabs AMD tabsettings init');
        }
    }
});
/* jshint ignore:end */
