<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.1 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Zikula\RoutesModule\Entity\Repository\Base\AbstractRouteRepository;

/**
 * Repository class used to implement own convenience methods for performing certain DQL queries.
 *
 * This is the concrete repository class for route entities.
 */
class RouteRepository extends AbstractRouteRepository
{
    /**
     * @inheritDoc
     */
    protected function genericBaseQueryAddOrderBy(QueryBuilder $qb, $orderBy = '')
    {
        if ($orderBy == 'sort asc') {
            $qb->addOrderBy('tbl.group', 'asc');
            $qb->addOrderBy('tbl.sort', 'asc');

            return $qb;
        }

        return parent::genericBaseQueryAddOrderBy($qb, $orderBy);
    }
}
