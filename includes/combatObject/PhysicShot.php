<?php

/**
 *  OPBE
 *  Copyright (C) 2015  Jstar
 *
 * This file is part of OPBE.
 * 
 * OPBE is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OPBE is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with OPBE.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OPBE
 * @author Jstar <frascafresca@gmail.com>
 * @copyright 2015 Jstar <frascafresca@gmail.com>
 * @license http://www.gnu.org/licenses/ GNU AGPLv3 License
 * @version 6-03-2015
 * @link https://github.com/jstar88/opbe
 */

class PhysicShot
{
    private $shipType;
    private $damage;
    private $count;

    private $assorbedDamage = 0;
    private $bouncedDamage = 0;
    private $hullDamage = 0;
    private $cellDestroyed = 0;


    /**
     * PhysicShot::__construct()
     * 
     * @param ShipType $shipType
     * @param int $damage
     * @param int $count
     * @return
     */
    public function __construct(ShipType $shipType, $damage, $count)
    {
        log_var('damage', $damage);
        log_var('count', $count);
        if ($damage < 0)
            throw new Exception('Negative damage');
        if ($count < 0)
            throw new Exception('Negative amount of shots');
        $this->fighters = $shipType->cloneMe();


	if($count<100) { $multiplier = 1.1+($count/1000); } else { $multiplier=1.2; }
	$destack = 0.1;
	$multiplier1 = $multiplier-$destack;
	$multiplier2 = $multiplier+$destack;

	if($GLOBALS['round']==1) { $this->damage=rand($damage*$multiplier1*1.199, $damage*$multiplier2*1.20); } else if($GLOBALS['round']==2) {$this->damage=rand($damage*$multiplier1*0.999, $damage*$multiplier2*1.00); } else if($GLOBALS['round']==3) { $this->damage=rand($damage*$multiplier1*0.299, $damage*$multiplier2*0.30); } else if($GLOBALS['round']==4){$this->damage=rand($damage*$multiplier1*0.249, $damage*$multiplier2*0.25);} else if($GLOBALS['round']==5) {$this->damage=rand($damage*$multiplier1*0.599, $damage*$multiplier2*0.60);} else { $this->damage = rand($damage*$multiplier1*0.499, $damage*$multiplier2*0.50); }
        $this->count = $count;
    }


    /**
     * PhysicShot::getAssorbedDamage()
     * Return the damage assorbed by shield
     * @return float
     */
    public function getAssorbedDamage($cell = false)
    {
        return $this->assorbedDamage;
    }


    /**
     * PhysicShot::getBouncedDamage()
     * Return the bounced damage
     * @return float
     */
    public function getBouncedDamage()
    {
        return $this->bouncedDamage;
    }


    /**
     * PhysicShot::getHullDamage()
     * Return the damage assorbed by hull
     * @return float
     */
    public function getHullDamage()
    {
        return $this->hullDamage;
    }


    /**
     * PhysicShot::getPureDamage()
     * Return the total amount of damage from enemy
     * @return int
     */
    public function getPureDamage()
    {
        return $this->damage * $this->count;
    }


    /**
     * PhysicShot::getHitShips()
     * Return the number of hitten ships.
     * @return
     */
    public function getHitShips()
    {
        return min($this->count, $this->fighters->getCount());
    }


    /**
     * PhysicShot::start()
     * Start the system
     * @return
     */
    public function start()
    {     
        $this->bounce();
        $this->assorb();
        $this->inflict();
    }
    
    
    /**
     * PhysicShot::bounce()
     * If the shield is disabled, then bounced damaged is zero.
     * If the damage is exactly a multipler of the needed to destroy one shield's cell then bounced damage is zero. 
     * If damage is more than shield,then bounced damage is zero.
     * 
     * @param int $currentCellsCount
     * @param int $cellsDestroyedInOneShot
     * @param float $bouncedDamageForOneShot
     * @return null
     */
    private function bounce()
    {
        $count = $this->count;
        $damage = $this->damage;
        $shieldCellValue = $this->fighters->getShieldCellValue();
        $unbauncedDamage = $this->clamp($damage, $shieldCellValue);
        $this->bouncedDamage = ($damage - $unbauncedDamage) * $count;
    }

    /**
     * PhysicShot::assorb()
     * If the shield is disabled, then assorbed damaged is zero.
     * If the total damage is more than shield, than the assorbed damage should equal the shield value.
     * @param int $currentCellsCount
     * @param int $cellsDestroyedInOneShot
     * @return null
     */
    private function assorb()
    {
        $count = $this->count;
        $damage = $this->damage;
        $shieldCellValue = $this->fighters->getShieldCellValue();
        $unbauncedDamage = $this->clamp($damage, $shieldCellValue);
        $currentShield = $this->fighters->getCurrentShield();
        if (USE_HITSHIP_LIMITATION)
        {
            $currentShield = $currentShield * $this->getHitShips() / $this->fighters->getCount();
        }
        $this->assorbedDamage = min($unbauncedDamage * $count, $currentShield);
    }

    /**
     * PhysicShot::inflict()
     * HullDamage should be more than zero and less than shiplife.
     * Expecially, it should be less than the life of hitten ships.
     * @return null
     */
    private function inflict()
    {
        $hullDamage = $this->getPureDamage() - $this->assorbedDamage - $this->bouncedDamage;
        $hullDamage = min($hullDamage, $this->fighters->getCurrentLife() * $this->getHitShips() / $this->fighters->getCount());
        $this->hullDamage = max(0, $hullDamage);
    }

    /**
     * PhysicShot2::clamp()
     * Return $a if greater than $b, zero otherwise
     * @param mixed $a
     * @param mixed $b
     * @return mized
     */
    private function clamp($a, $b)
    {
        if ($a > $b)
        {
            return $a;
        }
        return 0;
    }

}
