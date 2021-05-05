<?php

namespace App\Model;

class PropertyManager extends AbstractManager
{
    public const TABLE = 'property';

    public function insert(array $property): int
    {
        $query = "INSERT INTO " . self::TABLE . " (`reference`, `transaction`, `address`, `price`,
        `energy_performance`, `greenhouse_gases`, `description`, `sector_id`, `property_type_id`)
                VALUES (:reference, :transaction, :address, :price,
                :energyPerformance, :greenhouseGases, :description, :sector, :propertyType)";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue('reference', $property['reference'], \PDO::PARAM_STR);
        $statement->bindValue('transaction', $property['transaction'], \PDO::PARAM_STR);
        $statement->bindValue('address', $property['address'], \PDO::PARAM_STR);
        $statement->bindValue('price', $property['price'], \PDO::PARAM_INT);
        $statement->bindValue('energyPerformance', $property['energyPerformance'], \PDO::PARAM_STR);
        $statement->bindValue('greenhouseGases', $property['greenhouseGases'], \PDO::PARAM_STR);
        $statement->bindValue('description', $property['description'], \PDO::PARAM_STR);
        $statement->bindValue('sector', $property['sector'], \PDO::PARAM_INT);
        $statement->bindValue('propertyType', $property['propertyType'], \PDO::PARAM_INT);

        $statement->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function selectProperties(?string $transaction, ?int $propertyTypeId, ?int $sectorId, ?int $budget)
    {
        // prepared request
        $query = 'SELECT p.*, pt.name AS property_type, s.name AS sector_name, min(photo.name)';
        $query .= ' AS property_photo FROM ' . self::TABLE . ' p JOIN ' . PropertyTypeManager::TABLE;
        $query .= ' pt ON pt.id = p.property_type_id JOIN '  . SectorManager::TABLE . ' s ON s.id = p.sector_id';
        $query .= ' JOIN '  . PhotoManager::TABLE . ' ON photo.property_id = p.id';
        $queryParts = [];
        // Make the request that shows all the properties that correspond to the selected transaction type
        $queryParts = $this->buildCondition($queryParts, $transaction, 'transaction', 'transaction');
        // Make the request that shows all the properties that correspond to the selected property type
        $queryParts = $this->buildCondition($queryParts, strval($propertyTypeId), 'property_type_id', 'propertyTypeId');
         // Make the request that shows all the properties that correspond to the selected sector
        $queryParts = $this->buildCondition($queryParts, strval($sectorId), 'sector_id', 'sectorId');
         // Make the request that shows all the properties of which prices are less than or equal to the input price
        if ($budget) {
            $queryParts[] = "p.price <= :budget";
        }
        if (!empty($queryParts)) {
            $query .= " WHERE " . implode(" AND ", $queryParts);
        }
        $query .= " group by p.id";
        $statement = $this->pdo->prepare($query);
        if ($transaction) {
            $statement->bindValue('transaction', $transaction, \PDO::PARAM_STR);
        }
        if ($propertyTypeId) {
            $statement->bindValue('propertyTypeId', $propertyTypeId, \PDO::PARAM_INT);
        }
        if ($sectorId) {
            $statement->bindValue('sectorId', $sectorId, \PDO::PARAM_INT);
        }
        if ($budget) {
            $statement->bindValue('budget', $budget, \PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll();
    }
    // Created a method that add the conditions corresponding to the different search types into the origin request.
    private function buildCondition(array $queryParts, ?string $filter, ?string $tableColumn, ?string $paramId): array
    {
        if ($filter) {
            $queryParts[] = "p." . $tableColumn . " =:" . $paramId;
        }
        return $queryParts;
    }

    public function selectHomeSliderInfo(int $id)
    {
        // Retrieve data to be displayed right below estate info card in home 3-fold slider
        $query =  'SELECT PR.*, S.name as city, P.name as property_type FROM ' . static::TABLE . ' PR  ';
        $query .= 'INNER JOIN ' . SectorManager::TABLE . ' S ON PR.sector_id = S.id ';
        $query .= 'INNER JOIN ' . PropertyTypeManager::TABLE . '  P on PR.property_type_id = P.id ';
        $query .= 'WHERE PR.id=:id';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    public function selectPropertyTypeByPropertyId(int $id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM " . static::TABLE .
        " INNER JOIN propertyType ON propertyType.id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }
}
