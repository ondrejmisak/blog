<?php
namespace App\Form;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
        $builder
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
            ->add('info', FormType\TextareaType::class, [
                    'label' => 'Info',
                    'required' => true,
                    'empty_data' => '' ,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 1])
                    ]])
        ->add('submit', FormType\SubmitType::class,['label' => 'Uložiť',] );
            
           }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}