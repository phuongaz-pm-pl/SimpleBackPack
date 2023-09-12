<?php

declare(strict_types=1);

namespace phuongaz\simplebackpack\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use dktapps\pmforms\ModalForm;
use Generator;
use muqsit\invmenu\InvMenu;
use phuongaz\azeconomy\EcoAPI;
use phuongaz\itemholder\IHolderAPI;
use phuongaz\simplebackpack\BackPackTypes;
use phuongaz\simplebackpack\SimpleBackPack;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class FormStorage {

    public function send(Player $player) : void {
        Await::f2c(function () use ($player) {
            $db = SimpleBackPack::getInstance()->getBPDatabase();
            $type = yield from $db->getBackPackType($player->getName());
            $options = [];
            if(is_null($type)) {
                $options = [
                    new MenuOption("BUY BACKPACK")
                ];
            }else{
                $options[] = new MenuOption("OPEN!");
                $nextType = BackPackTypes::fromString($type);
                if(!is_null($nextType->next())) {
                    $options[] = new MenuOption("Upgrade with ". BackPackTypes::fromString($type)->next()->getCost());
                }
            }

            $mainChoose = yield from $this->menu($player, "BACKPACK", "Choose one", $options);
            $currency = SimpleBackPack::getInstance()->getConfig()->get("currency");
            $costOfDefault = BackPackTypes::CHEST->getCost();
            if($mainChoose == 0) {
                if(!is_null($type)) {
                    $backpackType = BackPackTypes::fromString($type);
                    $backpack = InvMenu::create($backpackType->toInvTypes());
                    IHolderAPI::get($player->getName(), function(array|Item|null $itemData) use ($player, $backpack) {
                        $itemData = (is_array($itemData)) ? $itemData : [$itemData];
                        $backpack->getInventory()->setContents($itemData);
                        $backpack->send($player);
                    });

                    $backpack->setInventoryCloseListener(function(Player $player, Inventory $inventory){
                        IHolderAPI::update($player->getName(), $inventory->getContents());
                    });
                    return;
                }
                $confirmBuy = yield from $this->modal(
                    $player,
                    "BUY BACKPACK",
                    "Do you want buy BackPack with $costOfDefault $currency",
                    "Yes",
                    "No"
                );
                if($confirmBuy) {
                    EcoAPI::removeCurrency($player->getName(), $currency, $costOfDefault, function(bool $isSuccess) use ($currency, $player, $db) {
                        if($isSuccess) {
                            Await::f2c(fn() => yield $db->upgradeBackPack($player->getName(), function(null|BackPackTypes $types) use ($player) {
                                $player->sendMessage("Bought BackPack ". $types->toString(). " successfully");
                            }));
                        }else {
                            $player->sendMessage("Not enough ". $currency);
                        }
                    });
                }
                return;
            }
            if($mainChoose == 1) {
                $nextCost = BackPackTypes::fromString($type)->next()->getCost();
                $confirmUpgrade = yield from $this->modal($player, "Upgrade BackPack", "Do you want upgrade BackPack with $nextCost $currency");
                if($confirmUpgrade) {
                    EcoAPI::removeCurrency($player->getName(), $currency, $nextCost, function(bool $isSuccess) use ($currency, $player, $db) {
                        if($isSuccess) {
                            Await::f2c(fn() => yield $db->upgradeBackPack($player->getName(), function(null|BackPackTypes $types) use ($player) {
                                if(!is_null($types)) {
                                    $player->sendMessage("Upgrade BackPack to ". $types->toString() . " successfully");
                                }
                            }));
                            return;
                        }
                        $player->sendMessage("Not enough ". $currency);
                    });
                }
            }
        });
    }

    public function custom(Player $player, string $title, array $elements): Generator {
        $f = yield Await::RESOLVE;
        $player->sendForm(new CustomForm(
            $title, $elements,
            function (Player $player, CustomFormResponse $result) use ($f): void {
                $f($result);
            },
            function (Player $player) use ($f): void {
                $f(null);
            }
        ));
        return yield Await::ONCE;
    }

    public function menu(Player $player, string $title, string $text, array $options): Generator {
        $f = yield Await::RESOLVE;
        $player->sendForm(new MenuForm(
            $title, $text, $options,
            function (Player $player, int $selectedOption) use ($f): void {
                $f($selectedOption);
            },
            function (Player $player) use ($f): void {
                $f(null);
            }
        ));
        return yield Await::ONCE;
    }

    public function modal(Player $player, string $title, string $text, string $yesButtonText = "gui.yes", string $noButtonText = "gui.no"): Generator {
        $f = yield Await::RESOLVE;
        $player->sendForm(new ModalForm(
            $title, $text,
            function (Player $player, bool $choice) use ($f): void {
                $f($choice);
            },
            $yesButtonText, $noButtonText
        ));
        return yield Await::ONCE;
    }
}