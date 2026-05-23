<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            [
                'symbol'      => 'XAU',
                'name'        => 'Gold',
                'unit'        => 'troy_oz',
                'currency'    => 'EUR',
                'spot_price'  => 1823.45,
                'bid_price'   => 1822.50,
                'ask_price'   => 1824.40,
                'spread'      => 1.90,
                'is_active'   => true,
                'metadata'    => ['purities' => ['999.9', '999', '585'], 'iso_code' => 'XAU'],
            ],
            [
                'symbol'      => 'XAG',
                'name'        => 'Silver',
                'unit'        => 'troy_oz',
                'currency'    => 'EUR',
                'spot_price'  => 23.12,
                'bid_price'   => 23.08,
                'ask_price'   => 23.16,
                'spread'      => 0.08,
                'is_active'   => true,
                'metadata'    => ['purities' => ['999', '925'], 'iso_code' => 'XAG'],
            ],
            [
                'symbol'      => 'XPT',
                'name'        => 'Platinum',
                'unit'        => 'troy_oz',
                'currency'    => 'EUR',
                'spot_price'  => 895.20,
                'bid_price'   => 893.80,
                'ask_price'   => 896.60,
                'spread'      => 2.80,
                'is_active'   => true,
                'metadata'    => ['purities' => ['999.5', '950'], 'iso_code' => 'XPT'],
            ],
            [
                'symbol'      => 'XPD',
                'name'        => 'Palladium',
                'unit'        => 'troy_oz',
                'currency'    => 'EUR',
                'spot_price'  => 1054.75,
                'bid_price'   => 1052.00,
                'ask_price'   => 1057.50,
                'spread'      => 5.50,
                'is_active'   => true,
                'metadata'    => ['purities' => ['999.5'], 'iso_code' => 'XPD'],
            ],
        ];

        foreach ($assets as $data) {
            Asset::updateOrCreate(['symbol' => $data['symbol']], $data);
        }

        $this->command->info('Assets seeded: XAU, XAG, XPT, XPD');
    }
}
