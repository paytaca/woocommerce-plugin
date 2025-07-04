<?php
//Use this function if using the packages in composer.json and has enabled the gmp extension
//Used for generating bch address using pubkey
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use CashAddr\CashAddress;

require_once __DIR__ . '/../vendor/autoload.php';

function generate_bch_address($xpub, $index) {
    try {
        Bitcoin::setNetwork(NetworkFactory::bitcoin());
        $factory = new HierarchicalKeyFactory();
        $hd = $factory->fromExtended($xpub);
        $child = $hd->derivePath("0/{$index}");
        $pubKeyHash = $child->getPublicKey()->getPubKeyHash()->getBinary();
        return CashAddress::pubKeyHash('bitcoincash', $pubKeyHash);
    } catch (\Exception $e) {
        throw new Exception("Address generation failed: " . $e->getMessage());
    }
}

