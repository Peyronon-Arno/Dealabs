<?php

namespace App\Form;

use App\Entity\Promo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class PromoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TypeTextType::class, [
                'label' => "Titre",
                'required' => true
            ])
            ->add('code', TypeTextType::class, [
                'label' => "Code promo",
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => "Description",
                'required' => true
            ])
            ->add('reduction', TypeTextType::class, [
                'label' => "Réduction",
                'required' => true
            ])
            ->add('expirationDate', DateType::class, [
                'label' => "Date d'expiration",
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promo::class,
        ]);
    }
}
