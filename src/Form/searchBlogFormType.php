<?php

namespace App\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type as FormType;


class searchBlogFormType extends AbstractType
{
    public function __construct(EntityManagerInterface $entityManager)
    {
         
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    { 
        $builder->add('tags', FormType\TextType::class, [
            'label' => 'Tagy',
            'mapped' => false,
            'required' => true,
            'attr' =>[
                'placeholder' => 'Viacero tagov oddeľ čiarkou alebo medzerou',
                'onchange' => 'this.form.submit()'
            ],
            'required' => false
        ]);
    } 
}