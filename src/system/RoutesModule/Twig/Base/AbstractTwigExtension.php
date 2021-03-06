<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.4 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Twig\Base;

use Twig_Extension;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\RoutesModule\Helper\ListEntriesHelper;
use Zikula\RoutesModule\Helper\EntityDisplayHelper;
use Zikula\RoutesModule\Helper\WorkflowHelper;

/**
 * Twig extension base class.
 */
abstract class AbstractTwigExtension extends Twig_Extension
{
    use TranslatorTrait;
    
    /**
     * @var VariableApiInterface
     */
    protected $variableApi;
    
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;
    
    /**
     * @var EntityDisplayHelper
     */
    protected $entityDisplayHelper;
    
    /**
     * @var WorkflowHelper
     */
    protected $workflowHelper;
    
    /**
     * @var ListEntriesHelper
     */
    protected $listHelper;
    
    /**
     * TwigExtension constructor.
     *
     * @param TranslatorInterface $translator     Translator service instance
     * @param VariableApiInterface $variableApi    VariableApi service instance
     * @param UserRepositoryInterface $userRepository UserRepository service instance
     * @param EntityDisplayHelper $entityDisplayHelper EntityDisplayHelper service instance
     * @param WorkflowHelper      $workflowHelper WorkflowHelper service instance
     * @param ListEntriesHelper   $listHelper     ListEntriesHelper service instance
     */
    public function __construct(
        TranslatorInterface $translator,
        VariableApiInterface $variableApi,
        UserRepositoryInterface $userRepository,
        EntityDisplayHelper $entityDisplayHelper,
        WorkflowHelper $workflowHelper,
        ListEntriesHelper $listHelper)
    {
        $this->setTranslator($translator);
        $this->variableApi = $variableApi;
        $this->userRepository = $userRepository;
        $this->entityDisplayHelper = $entityDisplayHelper;
        $this->workflowHelper = $workflowHelper;
        $this->listHelper = $listHelper;
    }
    
    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator Translator service instance
     */
    public function setTranslator(/*TranslatorInterface */$translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * Returns a list of custom Twig functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zikularoutesmodule_objectTypeSelector', [$this, 'getObjectTypeSelector']),
            new \Twig_SimpleFunction('zikularoutesmodule_templateSelector', [$this, 'getTemplateSelector']),
            new \Twig_SimpleFunction('zikularoutesmodule_userAvatar', [$this, 'getUserAvatar'], ['is_safe' => ['html']])
        ];
    }
    
    /**
     * Returns a list of custom Twig filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('zikularoutesmodule_listEntry', [$this, 'getListEntry']),
            new \Twig_SimpleFilter('zikularoutesmodule_formattedTitle', [$this, 'getFormattedEntityTitle']),
            new \Twig_SimpleFilter('zikularoutesmodule_objectState', [$this, 'getObjectState'], ['is_safe' => ['html']])
        ];
    }
    
    /**
     * The zikularoutesmodule_objectState filter displays the name of a given object's workflow state.
     * Examples:
     *    {{ item.workflowState|zikularoutesmodule_objectState }}        {# with visual feedback #}
     *    {{ item.workflowState|zikularoutesmodule_objectState(false) }} {# no ui feedback #}
     *
     * @param string  $state      Name of given workflow state
     * @param boolean $uiFeedback Whether the output should include some visual feedback about the state
     *
     * @return string Enriched and translated workflow state ready for display
     */
    public function getObjectState($state = 'initial', $uiFeedback = true)
    {
        $stateInfo = $this->workflowHelper->getStateInfo($state);
    
        $result = $stateInfo['text'];
        if (true === $uiFeedback) {
            $result = '<span class="label label-' . $stateInfo['ui'] . '">' . $result . '</span>';
        }
    
        return $result;
    }
    
    
    /**
     * The zikularoutesmodule_listEntry filter displays the name
     * or names for a given list item.
     * Example:
     *     {{ entity.listField|zikularoutesmodule_listEntry('entityName', 'fieldName') }}
     *
     * @param string $value      The dropdown value to process
     * @param string $objectType The treated object type
     * @param string $fieldName  The list field's name
     * @param string $delimiter  String used as separator for multiple selections
     *
     * @return string List item name
     */
    public function getListEntry($value, $objectType = '', $fieldName = '', $delimiter = ', ')
    {
        if ((empty($value) && $value != '0') || empty($objectType) || empty($fieldName)) {
            return $value;
        }
    
        return $this->listHelper->resolve($value, $objectType, $fieldName, $delimiter);
    }
    
    
    /**
     * The zikularoutesmodule_objectTypeSelector function provides items for a dropdown selector.
     *
     * @return string The output of the plugin
     */
    public function getObjectTypeSelector()
    {
        $result = [];
    
        $result[] = [
            'text' => $this->__('Routes'),
            'value' => 'route'
        ];
    
        return $result;
    }
    
    
    /**
     * The zikularoutesmodule_templateSelector function provides items for a dropdown selector.
     *
     * @return string The output of the plugin
     */
    public function getTemplateSelector()
    {
        $result = [];
    
        $result[] = [
            'text' => $this->__('Only item titles'),
            'value' => 'itemlist_display.html.twig'
        ];
        $result[] = [
            'text' => $this->__('With description'),
            'value' => 'itemlist_display_description.html.twig'
        ];
        $result[] = [
            'text' => $this->__('Custom template'),
            'value' => 'custom'
        ];
    
        return $result;
    }
    
    /**
     * The zikularoutesmodule_formattedTitle filter outputs a formatted title for a given entity.
     * Example:
     *     {{ myPost|zikularoutesmodule_formattedTitle }}
     *
     * @param object $entity The given entity instance
     *
     * @return string The formatted title
     */
    public function getFormattedEntityTitle($entity)
    {
        return $this->entityDisplayHelper->getFormattedTitle($entity);
    }
    
    /**
     * Displays the avatar of a given user.
     *
     * @param int|string $uid    The user's id or name
     * @param int        $width  Image width (optional)
     * @param int        $height Image height (optional)
     * @param int        $size   Gravatar size (optional)
     * @param string     $rating Gravatar self-rating [g|pg|r|x] see: http://en.gravatar.com/site/implement/images/ (optional)
     *
     * @return string
     */
    public function getUserAvatar($uid = 0, $width = 0, $height = 0, $size = 0, $rating = '')
    {
        if (!is_numeric($uid)) {
            $limit = 1;
            $filter = [
                'uname' => ['operator' => '=', 'operand' => $uid]
            ];
            $results = $this->userRepository->query($filter, [], $limit);
            if (!count($results)) {
                return '';
            }
    
            $uid = $results->getIterator()->getArrayCopy()[0]->getUname();
        }
        $params = ['uid' => $uid];
        if ($width > 0) {
            $params['width'] = $width;
        }
        if ($height > 0) {
            $params['height'] = $height;
        }
        if ($size > 0) {
            $params['size'] = $size;
        }
        if ($rating != '') {
            $params['rating'] = $rating;
        }
    
        include_once 'lib/legacy/viewplugins/function.useravatar.php';
    
        $view = \Zikula_View::getInstance('ZikulaRoutesModule');
        $result = smarty_function_useravatar($params, $view);
    
        return $result;
    }
}
