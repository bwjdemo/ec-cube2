<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Repository\Master;

use Eccube\Annotation\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Eccube\Repository\AbstractRepository;

/**
 * OrderStatusRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @Repository
 */
class OrderStatusRepository extends AbstractRepository
{
    /**
     * NOT IN で検索する.
     *
     * TODO Abstract メソッドにしたい
     *
     * @param array $criteria
     * @param array $orderBy
     * @param integer $limit
     * @param integer $offset
     * @return array
     * @see EntityRepository::findBy()
     */
    public function findNotContainsBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('o');

        foreach ($criteria as $col => $val) {
            $qb->andWhere($qb->expr()->notIn('o.'.$col, ':'.$col))
                ->setParameter($col, (array)$val);
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                if (array_values($orderBy) === $orderBy) { // 配列 or 連想配列
                    $sort = $order;
                    $order = 'ASC';
                }
                $qb->orderBy('o.'.$sort, $order);
            }
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllArray()
    {

        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT os FROM Eccube\Entity\Master\OrderStatus os INDEX BY os.id ORDER BY os.sort_no ASC')
        ;
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        return $result;

    }
}
