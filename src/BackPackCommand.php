<?php

declare(strict_types=1);

namespace phuongaz\simplebackpack;

use CortexPE\Commando\BaseCommand;
use phuongaz\simplebackpack\form\FormStorage;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BackPackCommand extends BaseCommand {

    private string $permission = "backpack.command";

    protected function prepare(): void {
        $this->setPermission($this->permission);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if($sender instanceof Player) {
            (new FormStorage())->send($sender);
        }
    }

    public function getPermission(): string {
        return $this->permission;
    }
}