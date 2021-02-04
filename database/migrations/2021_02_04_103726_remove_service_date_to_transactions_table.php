<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveServiceDateToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //KARENA TUGAS KITA ADALAH MENGHAPUS KEDUA FIELD TERSEBUT DARI TABLE TRANSACTIONS
            //MAKA PADA METHOD UP(), KITA GUNAKAN DROPCOLUMN()
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //SEDANGKAN PADA METHOD DOWN KITA BUAT LAGI FIELD TERSEBUT UNTUK MENGANTISIPASI ERROR JIKA SUATU SAAT KITA MELAKUKAN ROLLBACK
            $table->datetime('start_date')->nullable()->after('amount');
            $table->datetime('end_date')->nullable()->after('start_date');
        });
    }
}