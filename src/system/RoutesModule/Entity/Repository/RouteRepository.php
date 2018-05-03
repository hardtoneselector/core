<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 1.0.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Zikula\RoutesModule\Entity\Repository\Base\AbstractRouteRepository;

/**
 * Repository class used to implement own convenience methods for performing certain DQL queries.
 *
 * This is the concrete repository class for route entities.
 */
class RouteRepository extends AbstractRouteRepository
{
    /**
     * Deletes all custom routes for a certain bundle.
     *
     * @param string $bundleName Name of the bundle
     *
     * @return void
     *
     * @throws InvalidArgumentException Thrown if invalid parameters are received
     */
    public function deleteByBundle($bundleName)
    {
        // check id parameter
        if (empty($bundleName)) {
            throw new InvalidArgumentException('Invalid bundle name received.');
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->mainEntityClass, 'tbl')
           ->where('tbl.bundle = :bundle')
           ->setParameter('bundle', $bundleName);
        $query = $qb->getQuery();
        $query->execute();
    }
}
