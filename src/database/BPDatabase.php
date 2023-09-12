<?php

declare(strict_types=1);

namespace phuongaz\simplebackpack\database;

use Closure;
use Generator;
use phuongaz\azeconomy\EcoAPI;
use phuongaz\itemholder\IHolderAPI;
use phuongaz\simplebackpack\BackPackTypes;
use phuongaz\simplebackpack\listener\event\BackPackUpgradeEvent;
use phuongaz\simplebackpack\SimpleBackPack;
use pocketmine\Server;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;

class BPDatabase {

    CONST INIT = "backpacks.init";
    CONST GET = "backpacks.get";
    CONST INSERT = "backpacks.insert";
    CONST UPGRADE = "backpacks.upgrade";

    public function __construct(
        private DataConnector $connector
    ) {
        Await::f2c(fn() => $this->connector->asyncGeneric(self::INIT));
    }

    public function getBackPackType(string $username) : Generator {
        $connector = $this->connector;

        $rows = yield from $connector->asyncSelect(self::GET, ["username" => $username]);
        if(empty($rows)) {
            return null;
        }
        return $rows[0]["type"];
    }

    public function upgradeBackPack(string $username, ?Closure $callback = null): Generator {

        $oldType = yield from $this->getBackPackType($username);
        $backPackType = BackPackTypes::fromString($oldType);

        if (is_null($backPackType)) {
            $backPackType = BackPackTypes::CHEST;
            yield $this->connector->asyncInsert(self::INSERT, ["username" => $username, "type" => $backPackType->toString()]);
            IHolderAPI::register($username, []);
            if (!is_null($callback)) {
                $callback($backPackType);
            }
            return;
        }

        $nextType = $backPackType->next();

        if (!is_null($nextType)) {
            yield $this->connector->asyncChange(self::UPGRADE, ["username" => $username, "type" => $nextType->toString()]);
            if (!is_null($callback)) {
                $callback($backPackType);
            }
        }
    }

}