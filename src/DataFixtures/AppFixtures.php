<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Marchand;
use App\Entity\Deal;
use App\Entity\User;
use App\Entity\Promo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

        $user = new User();
        $user->setUsername('JohnDoe');
        $user->setEmail('johndoe@email.fr');
        $user->setPassword('$2y$13$g4fgykvkILE0mYhQEvkUO.HyHZlpT4zLFNnicu2iaxPpO/rDJtN');
        $user->setRoles(['ROLE_USER']);

        // Création de 10 catégories
        for ($i = 0; $i < 10; $i++) {
            $category = new Category();
            $category->setTitle($faker->word);
            $category->setDescription($faker->sentence(3));
            $manager->persist($category);
        }

        // Création de 100 deals
        for ($i = 0; $i < 100; $i++) {
            $deal = new Deal();
            $deal->setTitle($faker->sentence(3));
            $deal->setDescription($faker->sentence(30));
            $deal->setPrice($faker->randomFloat(2, 0, 1000));
            $deal->setCategory($category);
            $deal->setUser($user);
            $deal->setExpirationDate($faker->dateTimeBetween('now', '+1 year'));
            $deal->setCreatedAt($faker->dateTimeBetween('-1 month', 'now'));
            // Création de 5 commentaires par deal
            for ($j = 0; $j < rand(0, 10); $j++) {
                $comment = new Comment();
                $comment->setValue($faker->sentence(5));
                $comment->setUser($user);
                $comment->setDeal($deal);
                $deal->addComment($comment);
                $manager->persist($comment);
            }
            $manager->persist($deal);
        }

        $marchandname = ['aliexpress', 'amazon', 'apple', 'cdiscount', 'darty', 'samsung', 'sephora', 'zalando'];

        for ($i = 0; $i < sizeof($marchandname); $i++) {
            $merchant = new Marchand();
            $merchant->setName($marchandname[$i]);
            $merchant->setLink($faker->url);
            $merchant->setImage($marchandname[$i] . '.png');
            $manager->persist($merchant);
            // Création de 5 promos pour chaque marchand
            for ($j = 0; $j < 5; $j++) {
                $promo = new Promo();
                $promo->setTitle($faker->sentence);
                $promo->setExpirationDate($faker->dateTimeBetween('now', '+1 year'));
                $promo->setCode($faker->randomNumber(6));
                $promo->setDescription($faker->sentence(30));
                $promo->setReduction($faker->numberBetween(1, 50));
                $promo->setMarchand($merchant);
                $promo->setCreatedAt($faker->dateTimeBetween('-1 month', 'now'));
                // Création de 5 commentaires par deal
                for ($j = 0; $j < rand(0, 10); $j++) {
                    $comment = new Comment();
                    $comment->setValue($faker->sentence(5));
                    $comment->setUser($user);
                    $comment->setPromo($promo);
                    $promo->addComment($comment);
                    $manager->persist($comment);
                }
                $manager->persist($promo);
            }
        }

        $badgeTitles = ["Badge surveillant", "Badge cobaye", "Badge rapport de stage"];
        $badgeDescriptions = ["Pour obtenir ce badge, votez un minimum de 10 deals", "Pour obtenir ce badge, postez un minimum de 10 deals", "Pour obtenir ce badge, commentez au minimum de 10 fois (deals / codes promos)"];

        for ($i = 0; $i < 3; $i++) {
            $badge = new Badge();
            $badge->setTitle($badgeTitles[$i]);
            $badge->setDescription($badgeDescriptions[$i]);
            $badge->setCurrentValue(0);
            $badge->setGoalValue(10);
            $manager->persist($badge);
            $user->addBadge($badge);
        }
        $manager->persist($user);
        $manager->flush();
    }
}
