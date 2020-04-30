<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands\relations;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyFactions\commands\subcommands\FactionSubCommand;
use DaPigGuy\PiggyFactions\event\relation\FactionRelationEvent;
use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\factions\FactionsManager;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\players\FactionsPlayer;
use DaPigGuy\PiggyFactions\utils\Relations;
use pocketmine\Player;

class NeutralSubCommand extends FactionSubCommand
{
    public function onNormalRun(Player $sender, ?Faction $faction, FactionsPlayer $member, string $aliasUsed, array $args): void
    {
        $targetFaction = FactionsManager::getInstance()->getFactionByName($args["faction"]);
        if ($targetFaction === null) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.invalid-faction", ["{FACTION}" => $args["faction"]]);
            return;
        }
        if ($faction->getRelation($targetFaction) === Relations::NONE) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.neutral.already-neutral", ["{FACTION}" => $targetFaction->getName()]);
            return;
        }
        $ev = new FactionRelationEvent([$faction, $targetFaction], Relations::NONE);
        $ev->call();
        if ($ev->isCancelled()) return;

        $faction->setRelation($targetFaction, Relations::NONE);
        foreach ($faction->getOnlineMembers() as $m) {
            LanguageManager::getInstance()->sendMessage($m, "commands.neutral.success", ["{FACTION}" => $targetFaction->getName()]);
        }
        if ($targetFaction->isAllied($faction) || $targetFaction->isTruced($faction)) {
            foreach ($targetFaction->getOnlineMembers() as $m) {
                LanguageManager::getInstance()->sendMessage($m, "commands.neutral.success", ["{FACTION}" => $faction->getName()]);
            }
            $targetFaction->setRelation($faction, Relations::NONE);
        }
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("faction"));
    }
}