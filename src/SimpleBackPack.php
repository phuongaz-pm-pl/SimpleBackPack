<?php

declare(strict_types=1);

namespace phuongaz\simplebackpack;

use phuongaz\simplebackpack\database\BPDatabase;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\libasynql;

class SimpleBackPack extends PluginBase {
    use SingletonTrait;

    private BPDatabase $database;

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $connector = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
        ]);
        $this->database = new BPDatabase($connector);
        $this->getServer()->getCommandMap()->register("SimpleBackPack", new BackPackCommand($this, "backpack", "BackPack command"));
    }

    public function getBPDatabase() : BPDatabase {
        return $this->database;
    }
}