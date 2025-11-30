
  <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConvertedAmountToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'amount_converted')) {
                $table->decimal('amount_converted', 12, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'converted_currency')) {
                $table->string('converted_currency', 5)->default('UGX')->after('amount_converted');
            }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'converted_currency')) {
                $table->dropColumn('converted_currency');
            }
            if (Schema::hasColumn('payments', 'amount_converted')) {
                $table->dropColumn('amount_converted');
            }
        });
    }
}
;
