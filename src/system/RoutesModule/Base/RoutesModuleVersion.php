<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.0 (http://modulestudio.de) at Mon Nov 02 22:41:56 CET 2015.
 */

namespace Zikula\RoutesModule\Base;

use HookUtil;
use Zikula_AbstractVersion;
use Zikula\Component\HookDispatcher\ProviderBundle;
use Zikula\Component\HookDispatcher\SubscriberBundle;
use Zikula\SearchModule\AbstractSearchable;

/**
 * Version information base class.
 */
class RoutesModuleVersion extends Zikula_AbstractVersion
{
    /**
     * Retrieves meta data information for this application.
     *
     * @return array List of meta data.
     */
    public function getMetaData()
    {
        $meta = array();
        // the current module version
        $meta['version']              = '1.0.1';
        // the displayed name of the module
        $meta['displayname']          = $this->__('Routes');
        // the module description
        $meta['description']          = $this->__('Routes module generated by ModuleStudio 0.7.0.');
        //! url version of name, should be in lowercase without space
        $meta['url']                  = $this->__('routes');
        // core requirement
        $meta['core_min']             = '1.4.1'; // requires minimum 1.4.1 or later
        $meta['core_max']             = '1.4.99'; // not ready for 1.5.0 yet

        // define special capabilities of this module
        $meta['capabilities'] = array(
              HookUtil::SUBSCRIBER_CAPABLE => array('enabled' => true)/*,
              HookUtil::PROVIDER_CAPABLE => array('enabled' => true), // TODO: see #15
*/
        );

        // permission schema
        $meta['securityschema'] = array(
            'ZikulaRoutesModule::' => '::',
            'ZikulaRoutesModule::Ajax' => '::',
            'ZikulaRoutesModule:Route:' => 'Route ID::',
        );


        return $meta;
    }

    /**
     * Define hook subscriber bundles.
     */
    protected function setupHookBundles()
    {
        $bundle = new SubscriberBundle($this->name, 'subscriber.zikularoutesmodule.ui_hooks.routes', 'ui_hooks', $this->__('zikularoutesmodule Routes Display Hooks'));
        
        // Display hook for view/display templates.
        $bundle->addEvent('display_view', 'zikularoutesmodule.ui_hooks.routes.display_view');
        // Display hook for create/edit forms.
        $bundle->addEvent('form_edit', 'zikularoutesmodule.ui_hooks.routes.form_edit');
        // Display hook for delete dialogues.
        $bundle->addEvent('form_delete', 'zikularoutesmodule.ui_hooks.routes.form_delete');
        // Validate input from an ui create/edit form.
        $bundle->addEvent('validate_edit', 'zikularoutesmodule.ui_hooks.routes.validate_edit');
        // Validate input from an ui create/edit form (generally not used).
        $bundle->addEvent('validate_delete', 'zikularoutesmodule.ui_hooks.routes.validate_delete');
        // Perform the final update actions for a ui create/edit form.
        $bundle->addEvent('process_edit', 'zikularoutesmodule.ui_hooks.routes.process_edit');
        // Perform the final delete actions for a ui form.
        $bundle->addEvent('process_delete', 'zikularoutesmodule.ui_hooks.routes.process_delete');
        $this->registerHookSubscriberBundle($bundle);

        $bundle = new SubscriberBundle($this->name, 'subscriber.zikularoutesmodule.filter_hooks.routes', 'filter_hooks', $this->__('zikularoutesmodule Routes Filter Hooks'));
        // A filter applied to the given area.
        $bundle->addEvent('filter', 'zikularoutesmodule.filter_hooks.routes.filter');
        $this->registerHookSubscriberBundle($bundle);

        
    }
}