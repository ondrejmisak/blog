<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\BlogPosts;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
        $builder
        ->add('post', FormType\HiddenType::class, [
            'mapped'=>false,
            'data' => $options['data']->getId(),
        ])
        ->add('parent', FormType\HiddenType::class, [
            'mapped'=>false
        ])
        ->add('depth', FormType\HiddenType::class, [
            'mapped'=>false,
            'data' =>0,
        ])
        ->add('comment', FormType\TextareaType::class, [
                    'required' => true,
                    'mapped'=>false,
                    'empty_data' => '' ,
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Zadaj komentár']),
                        new Assert\Length(['min' => 1])
                    ]])
        ->add('submit', FormType\SubmitType::class,['label' => 'Odoslať','attr' =>['style' => 'float:right;']] );
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BlogPosts::class,
        ]);
    }
}