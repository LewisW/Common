<?php


namespace Vivait\Common\Model\Tenant;


use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

/**
 * @mixin EntityRepository
 */
trait SecureTenantTrait {
	public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
		trigger_error('Attempting to use find on a tenanted repository, this is unsafe', E_USER_WARNING);
		return parent::find($id, $lockMode, $lockVersion);
	}

	public function findAll() {
		trigger_error('Attempting to use findAll on a tenanted repository, this is unsafe', E_USER_WARNING);
		return parent::findAll();
	}

	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
		trigger_error('Attempting to use findBy on a tenanted repository, this is unsafe', E_USER_WARNING);
		return parent::findBy($criteria, $orderBy, $limit, $offset);
	}

	public function findOneBy(array $criteria, array $orderBy = null) {
		trigger_error('Attempting to use findOneBy on a tenanted repository, this is unsafe', E_USER_WARNING);
		return parent::findOneBy($criteria, $orderBy);
	}

	/**
	 * @param $id
	 * @param $tenant_ids
	 * @return bool|mixed
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function findSafe($id, $tenant_ids) {
		$qb = $this->createQueryBuilder('c');

		$tenant_alias = $this->buildTenantQuery($qb, 'c');

		$qb
			->andWhere('c.id = :id')
			->andWhere($qb->expr()->in($tenant_alias .'t.id', $tenant_ids))
			->setParameter('id', $id)
		;

		try {
			return $qb
				->getQuery()
				->getSingleResult()
				;
		}
		catch (NoResultException $e) {
			return false;
		}
	}

	public function buildTenantQuery(QueryBuilder $query, $table_alias) {
		$query
			// Join on the queue and the tenants
			->leftJoin($table_alias .'.queue', $table_alias .'q')
			->leftJoin($table_alias .'q.tenants', ($tenant_alias = $table_alias .'t'))
		;

		return $tenant_alias;
	}
}