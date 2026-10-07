<?php

namespace IsiKuota\Laravel\Console;

use IsiKuota;
use Illuminate\Console\Command;

class BalanceCommand extends Command
{
    protected $signature = 'isikuota:balance';

    protected $description = 'Cek saldo IsiKuota (sekaligus menguji kredensial, jam server, dan whitelist IP)';

    public function handle(IsiKuota $client): int
    {
        try {
            $res = $client->balance();
        } catch (\IsiKuotaException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($res['success'])) {
            $this->error(($res['code'] ?? 'ERROR') . ' - ' . ($res['message'] ?? 'Request ditolak.'));

            return self::FAILURE;
        }

        $this->info('Akun : ' . $res['data']['name']);
        $this->info('Saldo: Rp ' . number_format($res['data']['balance'], 0, ',', '.'));

        return self::SUCCESS;
    }
}
