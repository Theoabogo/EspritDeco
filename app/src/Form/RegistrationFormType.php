<?php

namespace App\Form;

use App\Entity\User;
use RegexIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;


class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
          ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => false, 
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions.',
                    ]),
                ],
            ])
           ->add('plainPassword', PasswordType::class, [
    'mapped' => false,
    'attr' => ['autocomplete' => 'new-password'],
    'constraints' => [
        new NotBlank([
            'message' => 'Veuillez entrer un mot de passe',
        ]),
        new Length([
            'min' => 12,
            'minMessage' => 'Le mot de passe doit contenir au moins 12 caractères',
        ]),
        new Regex([
            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
            'message' => 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ]),
    ],
])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
