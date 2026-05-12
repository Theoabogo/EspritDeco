<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullname', TextType::class, [
                'label' => 'Nom et Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le nom et prénom sont requis.'),
                    new Length(max: 255),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(message: "L'adresse est requise."),
                    new Length(max: 255),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new NotBlank(message: 'Le code postal est requis.'),
                    new Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres.'),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new NotBlank(message: 'La ville est requise.'),
                    new Length(max: 100),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
