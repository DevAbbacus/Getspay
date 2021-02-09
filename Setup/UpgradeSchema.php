<?php

namespace Magemonkeys\Gestpay\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

	public function upgrade(SchemaSetupInterface $setup,

		ModuleContextInterface $context) {

		$setup->startSetup();

		if (version_compare($context->getVersion(), '2.1.12') < 0) {

			// Get module table

			$tableName = $setup->getTable('easynolo_bancasellapro_token');

			// Check if the table already exists

			if ($setup->getConnection()->isTableExists($tableName) == true) {

				// Declare data

				$columns = [

					'token_type' => [

						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,

						'nullable' => true,

						'comment' => 'Token Type(Credit Card,Paypal,Slimpay etc.)',

					],
					'is_default' => [

						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,

						'nullable' => false,

						'default' => 0,

						'comment' => 'Flag for token is deafult for recurring payment or not.',

					],
				];

				$connection = $setup->getConnection();

				foreach ($columns as $name => $definition) {

					$connection->addColumn($tableName, $name, $definition);

				}

			}

		}

		$setup->endSetup();

	}

}