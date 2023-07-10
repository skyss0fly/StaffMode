<?php
 
namespace SleakGaming1\StaffMode;
 
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\form\Form;
use pocketmine\form\FormIcon;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\form\SimpleForm;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
 
class StaffMode extends PluginBase implements Listener {
 
    private $staffModePlayers = [];
 
    public function onEnable(): void {
        $this->getLogger()->info("StaffMode plugin enabled.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
 
    public function onDisable(): void {
        $this->getLogger()->info("StaffMode plugin disabled.");
    }
 
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (($command->getName() === "sm" || $command->getName() === "staffmode") && $sender instanceof Player) {
            if ($sender->hasPermission("staffmode.command")) {
                $this->toggleStaffMode($sender);
            } else {
                $sender->sendMessage(TextFormat::RED . "You don't have permission to use staff mode.");
            }
         elif (!$sender instanceof Player) {
          $sender->sendMessage(TextFormat::RED . "You must be In game to execute this game");
         return false;
        }
            return true;
        }
        return false;
    }
 
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if ($this->isInStaffMode($player) && $event->getItem() instanceof Item && $event->getItem()->getId() === VanillaItems::COMPASS) {
            $this->openPlayerListForm($player);
        }
    }
    
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if ($this->isInStaffMode($player)) {
            $this->toggleStaffMode($player);
        }
    }
 
    private function toggleStaffMode(Player $player): void {
        if ($this->isInStaffMode($player)) {
            $this->disableStaffMode($player);
        } else {
            $this->enableStaffMode($player);
        }
    }
 
    private function enableStaffMode(Player $player): void {
        $this->staffModePlayers[$player->getName()] = true;
 
        // Clear inventory
        $player->getInventory()->clearAll();
 
        // Give compass
  $compass = VanillaItems::COMPASS();
        $player->getInventory()->setItem(4, $compass);
 
        // Set flying mode
        $player->setAllowFlight(true);
        $player->setFlying(true);
 
        $player->sendMessage(TextFormat::GREEN . "Staff mode enabled.");
    }
 
    private function disableStaffMode(Player $player): void {
        unset($this->staffModePlayers[$player->getName()]);
 
        // Clear inventory
        $player->getInventory()->clearAll();
 
        // Set flying mode
        $player->setAllowFlight(false);
        $player->setFlying(false);
 
        $player->sendMessage(TextFormat::RED . "Staff mode disabled.");
    }
 
    private function isInStaffMode(Player $player): bool {
        return isset($this->staffModePlayers[$player->getName()]);
    }
 
    private function openPlayerListForm(Player $player): void {
        $onlinePlayers = $this->getOnlinePlayers();
 
        $options = [];
        foreach ($onlinePlayers as $onlinePlayer) {
            $option = new MenuOption($onlinePlayer->getName());
            $options[] = $option;
        }
 
        $form = new SimpleForm(function (Player $player, ?int $data) use ($onlinePlayers) {
            if ($data !== null && isset($onlinePlayers[$data])) {
                $selectedPlayer = $onlinePlayers[$data];
                $player->teleport($selectedPlayer);
                $player->sendMessage(TextFormat::GREEN . "Teleported to " . $selectedPlayer->getName() . ".");
            }
        });
 
        $form->setTitle("Player List");
        $form->addOptions($options);
 
        $player->sendForm($form);
    }
 
    private function getOnlinePlayers(): array {
        $players = [];
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if (!$this->isInStaffMode($player)) {
                $players[] = $player;
            }
        }
        return $players;
    }
 
}
