<?php

declare(strict_types=1);

namespace phuongaz\simplebackpack;

use muqsit\invmenu\type\InvMenuTypeIds;

enum BackPackTypes {

    case CHEST;
    case DOUBLE_CHEST;

    public function getCost() : int {
        $config = SimpleBackPack::getInstance();
        $backpacks = $config->getConfig()->get("backpacks");

        return match ($this) {
          self::CHEST => $backpacks["chest"],
          self::DOUBLE_CHEST => $backpacks["double-chest"]
        };
    }

    public function toString() : string {
        return match ($this) {
            self::CHEST => "chest",
            self::DOUBLE_CHEST => "double_chest",
        };
    }

    public function toInvTypes() : string {
        return match ($this) {
            self::CHEST => InvMenuTypeIds::TYPE_CHEST,
            self::DOUBLE_CHEST => InvMenuTypeIds::TYPE_DOUBLE_CHEST
        };
    }

    public function next() : ?BackPackTypes {
        return match ($this) {
            self::CHEST => self::DOUBLE_CHEST,
            self::DOUBLE_CHEST => null
        };
    }

    public static function fromString(?string $type) : ?BackPackTypes {
        return match($type) {
            "chest" => self::CHEST,
            "double_chest" => self::DOUBLE_CHEST,
            default => null
        };
    }
}