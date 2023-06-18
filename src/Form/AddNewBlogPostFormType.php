<?php
namespace App\Form;
use App\Entity\BlogPosts;
use App\Entity\Tags;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;   
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddNewBlogPostFormType extends AbstractType
{
    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;   
        $this->urlGenerator  = $urlGenerator;    
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
         
        $tagsArray = array();
        $postTags = $this->entityManager->getRepository(Tags::class)->findBy(['post'=>$options['data']->getId()]);
         
        foreach($postTags as $tags){                            
            $tagsArray[]=$tags->getTag();            
        };

        $href =  $this->urlGenerator->generate('settings');
        $script = <<< EOT
                        location.href='$href';
                    EOT;

        $builder            
            ->add('title', FormType\TextType::class, [
                'label' => 'Názov',
                'required' => true,
                'empty_data' => '' ,
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Názov nesmie byť prázdny']),
                    new Assert\Length(['min' => 1, 'minMessage' => 'Názov článku musí obsahovať minimálne {{ limit }} písmeno'])
                ]])
                ->add('text', FormType\TextareaType::class, [
                    'label' => 'Text',
                    'required' => true,
                    'empty_data' => '' ,
                    'constraints' => [
                        new Assert\NotBlank(['message'=>'Text článku nesmie byť prázdny']),
                    new Assert\Length(['min' => 1, 'minMessage' => 'Text článku musí obsahovať minimálne {{ limit }} písmeno'])
                    ]])
                
                ->add('date', DateTimeType::class,[
                    'label' => 'Dátum',
                    'widget' => 'choice',
                    'data' => new \DateTime("now")
                ])
                ->add('tags', FormType\TextType::class, [
                    'label' => 'Tagy',
                    'required' => false,
                    'empty_data' => '' ,
                    'mapped' => false,
                    'data' => implode(",",$tagsArray),
                    'attr' => ['placeholder'=>"tagy oddeľ čiarkou alebo medzerou"],
                    ])
                ->add('photo', FileType::class,[
                    'label' => 'Úvodá fotka',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/jpg',
                                'image/pjpeg', 
                                'image/png',
                                'image/gif'
                            ],
                        'mimeTypesMessage' => 'Povolené sú .jpg, .jpeg, .png, .gif',
                        ])
                    ],
                    
                ])  
            ->add('submit', FormType\SubmitType::class,['label' => 'Uložiť','attr' =>['style' => 'float:right;']] )
            ->add('back', FormType\ButtonType::class,[  'label' => 'Späť','attr' =>['class' => 'btn btn-secondary btn-lg mt-2 ', 'style' => '', 'onclick' =>$script],] );
            
           }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BlogPosts::class,
        ]);
    }
}