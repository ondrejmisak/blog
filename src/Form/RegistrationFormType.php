<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type as FormType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',FormType\EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'empty_data' => '' ,
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Email nesmie byť prázdny']),
                    new Assert\Length(['min' => 1, 'minMessage' => 'Email je príliš krátky'])
                ]])  
            ->add('name', FormType\TextType::class, [
                'label' => 'Meno',
                'required' => true,
                'empty_data' => '' ,
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Meno nesmie byť prázdne']),
                    new Assert\Length(['min' => 1, 'minMessage' => 'Meno musí obsahovať minimálne {{ limit }} písmeno'])
                ]])  
                ->add('surname', FormType\TextType::class, [
                    'label' => 'Priezvisko',
                    'required' => true,
                    'empty_data' => '' ,
                    'constraints' => [
                        new Assert\NotBlank(['message'=>'Priezvisko nesmie byť prázdne']),
                        new Assert\Length(['min' => 1, 'minMessage' => 'Priezvisko musí obsahovať minimálne {{ limit }} písmeno'])
                    ]])          
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'label' => 'Heslo',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Heslo nesmie byť prázdne']),
                    new Length([
                        'min' => 1,
                        'minMessage' => 'Heslo musí obsahovať minimálne {{ limit }} písmeno',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
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
