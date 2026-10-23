<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023044545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabele material, product i bom_item (pozycje BOM)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bom_item (id INT AUTO_INCREMENT NOT NULL, quantity NUMERIC(10, 3) NOT NULL, product_id INT NOT NULL, material_id INT NOT NULL, UNIQUE INDEX uniq_bom_item_product_material (product_id, material_id), INDEX IDX_BA64FA3F4584665A (product_id), INDEX IDX_BA64FA3FE308AC6F (material_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE material (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, unit VARCHAR(10) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, margin_percent NUMERIC(5, 2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bom_item ADD CONSTRAINT FK_BA64FA3F4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bom_item ADD CONSTRAINT FK_BA64FA3FE308AC6F FOREIGN KEY (material_id) REFERENCES material (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bom_item DROP FOREIGN KEY FK_BA64FA3F4584665A');
        $this->addSql('ALTER TABLE bom_item DROP FOREIGN KEY FK_BA64FA3FE308AC6F');
        $this->addSql('DROP TABLE bom_item');
        $this->addSql('DROP TABLE material');
        $this->addSql('DROP TABLE product');
    }
}
