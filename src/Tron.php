<?php

namespace PavloDotDev\LaravelTronModule;

use BIP\BIP44;
use Decimal\Decimal;
use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\BIP39\WordList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PavloDotDev\LaravelTronModule\Actions\Transfer\Preview;
use PavloDotDev\LaravelTronModule\Actions\Transfer\Send;
use PavloDotDev\LaravelTronModule\Concerns\ManageWallet;
use PavloDotDev\LaravelTronModule\Concerns\MnemonicHelpers;
use PavloDotDev\LaravelTronModule\DTO\AddressInfoDTO;
use PavloDotDev\LaravelTronModule\DTO\AddressTrc20Info;
use PavloDotDev\LaravelTronModule\Exceptions\WalletLocked;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronTrc20;
use PavloDotDev\LaravelTronModule\Models\TronWallet;
use Tron\Support\Key;
use Tron\Support\Key as SupportKey;

class Tron
{
    use ManageWallet;
    use MnemonicHelpers;

    protected Collection $trc20;

    public function __construct(
        protected readonly \IEXBase\TronAPI\Tron $api
    )
    {
        $this->trc20 = TronTrc20::get();
    }

    public function getTrc20AbiData(): array
    {
        return json_decode(File::get(__DIR__ . '/trc20.json'), true);
    }

    public function getTrc20(string $address = null): Collection|TronTrc20|array|null
    {
        if ($address !== null) {
            return $this->trc20->firstWhere('address', $address);
        }

        return $this->trc20;
    }

    public function createTrc20(string $contractAddress): TronTrc20
    {
        $contract = $this->api->contract($contractAddress);
        $name = $contract->name();
        $symbol = $contract->symbol();
        $decimals = $contract->decimals();

        $trc20 = TronTrc20::updateOrCreate([
            'address' => $contractAddress
        ], compact('name', 'symbol', 'decimals'));

        $this->trc20 = TronTrc20::get();

        return $trc20;
    }

    public function generateAddress(TronWallet $wallet, string $title = null): TronAddress
    {
        if (!$wallet->encrypted()->isUnlocked()) {
            throw new WalletLocked();
        }

        $lock = Cache::lock(__CLASS__ . ':' . __METHOD__ . ':' . $wallet->id, 10);
        if (!$lock->get()) {
            throw new \Exception('Lock timeout');
        }

        $index = $wallet->addresses()->max('index');
        $index = $index === null ? 0 : ($index + 1);

        $hdKey = BIP44::fromMasterSeed($wallet->encrypted()->seed())
            ->derive("m/44'/195'/0'/0")
            ->deriveChild($index);
        $privateKey = (string)$hdKey->privateKey;

        $address = SupportKey::getBase58CheckAddress('41' . Key::privateKeyToAddress($privateKey));

        $tronAddress = $wallet->addresses()->create([
            'address' => $address,
            'title' => $title,
            'index' => $index,
            'private_key' => $wallet->encrypted()->encode($privateKey),
        ]);

        $lock->release();

        return $tronAddress;
    }

    /**
     * Метод получения информации об адресе
     */
    public function getAddressInfo(TronAddress|string $address): AddressInfoDTO
    {
        return AddressInfoDTO::fromApi(
            address: $address instanceof TronAddress ? $address->address : $address,
            api: $this->api,
        );
    }

    public function getAddressTrc20Info(TronAddress|string $address): AddressTrc20Info
    {
        return AddressTrc20Info::fromApi(
            address: $address instanceof TronAddress ? $address->address : $address,
            trc20: $this->trc20,
            api: $this->api,
        );
    }

    /**
     * Проверка возможности перевода TRX с одного адреса на другой, и расчет комиссий.
     */
    public function transferPreview(TronAddress|string $from, TronAddress|string $to, string|int|float|Decimal $amount): Preview
    {
        return new Preview(
            from: $from instanceof TronAddress ? $from->address : $from,
            to: $to instanceof TronAddress ? $to->address : $to,
            amount: $amount instanceof Decimal ? $amount : (new Decimal((string)$amount))->round(6)
        );
    }

    /**
     * Перевод TRX с одного адреса на другой.
     */
    public function transferSend(TronWallet $wallet, TronAddress $from, TronAddress|string $to, string|int|float|Decimal $amount): Send
    {
        $preview = self::transferPreview(
            from: $from,
            to: $to,
            amount: $amount
        );

        return new Send(
            wallet: $wallet,
            from: $from,
            preview: $preview,
            api: $this->api
        );
    }

    public function createTransfer(TronAddress|string $from, TronAddress|string $to, string|int|float|Decimal $amount, string $message = null): array
    {
        $response = $this->api->getTransactionBuilder()->sendTrx(
            to: $to instanceof TronAddress ? $to->address : $to,
            amount: $amount instanceof Decimal ? $amount->toFloat() : floatval($amount),
            from: $from instanceof TronAddress ? $from->address : $from,
            message: $message
        );
        if (isset($response['Error'])) {
            throw new \Exception($response['Error']);
        }
        return $response;
    }

    public function previewTrc20Transfer(TronTrc20|string $contract, TronAddress|string $from, TronAddress|string $to, string|int|float|Decimal $amount): \PavloDotDev\LaravelTronModule\Actions\Trc20\Preview
    {
        $contractAddress = $contract instanceof TronTrc20 ? $contract->address : $contract;

        return new \PavloDotDev\LaravelTronModule\Actions\Trc20\Preview(
            contractAddress: $contractAddress,
            from: $from instanceof TronAddress ? $from->address : $from,
            to: $to instanceof TronAddress ? $to->address : $to,
            amount: $amount instanceof Decimal ? $amount : new Decimal((string)$amount),
            api: $this->api
        );
    }
}
