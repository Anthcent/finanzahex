<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OptimizePrintingCustomerLookup extends Migration
{
    public function up()
    {
        if ($this->db->DBDriver !== 'Postgre') {
            return;
        }

        $this->db->query(
            'CREATE INDEX IF NOT EXISTS idx_print_orders_customer_created '
            . 'ON print_orders (LOWER(customer_name), created_at DESC)'
        );
        $this->db->query(
            'CREATE INDEX IF NOT EXISTS idx_print_orders_status_created '
            . 'ON print_orders (status, created_at DESC)'
        );
        $this->db->query(
            'CREATE INDEX IF NOT EXISTS idx_customers_name_lower '
            . 'ON customers (LOWER(name))'
        );
    }

    public function down()
    {
        if ($this->db->DBDriver !== 'Postgre') {
            return;
        }

        $this->db->query('DROP INDEX IF EXISTS idx_print_orders_customer_created');
        $this->db->query('DROP INDEX IF EXISTS idx_print_orders_status_created');
        $this->db->query('DROP INDEX IF EXISTS idx_customers_name_lower');
    }
}
