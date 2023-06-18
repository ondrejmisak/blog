<?php
namespace App\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BlogPosts;
Use App\Entity\Comments;
Use App\Entity\Tags;
use App\Entity\User;

use App\Form\searchBlogFormType;
Use App\Form\AddNewBlogPostFormType;
use App\Form\CommentFormType;
Use App\Form\UserFormType;


class BlogController extends AbstractController
{   
    function __construct(SluggerInterface $slugger, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->slugger       = $slugger;
        $this->entityManager = $entityManager;
        $this->urlGenerator  = $urlGenerator;
        $this->security      = $security;
    }

    //Zoznam clankov
    #[Route('/',name:'index') ]
    #[Route('/index',name:'home') ]
    public function index(Request $request) : Response 
    {   
        //Vyhladavaci formular
        $form = $this->createForm(searchBlogFormType::class, [], [
            'action' => $this->urlGenerator->generate('index'),
            'method' => 'GET',
        ]); 
        $form->handleRequest($request); 
        $search = array();
        if ($form->isSubmitted())
        {
            $search = $_GET['search_blog_form'];
        }
        

        $posts = $this->entityManager->getRepository(BlogPosts::class)->findByFilter($search,['date'=>'DESC']);
        $postTags = array();

        //priradenie tagov k id clanku
        foreach($posts as $post)
        {
            $tag[$post->getId()] = $this->entityManager->getRepository(Tags::class)->findBy(['post'=> $post->getId()]);
            $tagArray = array();    
            foreach($tag[$post->getId()] as $tagWord)
            {   
                $tagArray[] = $tagWord->getTag();
                $postTags[$post->getid()] = $tagArray;
            }
        }

        return $this->render('blog/index.html.twig', [
             'posts' => $posts,
             'postTags' => $postTags,
             'searchForm' => $form,
        ]);
    }

    //Zobrazenie jedneho clanku
    #[Route('/post/{id}',name:'post') ]
    public function blogPost(int $id = null)  
    {   
        if(!$id)
        {
            return new RedirectResponse($this->urlGenerator->generate('index'));        
        }

        $blogPost = $this->entityManager->getRepository(BlogPosts::class)->find($id);
        if(!$blogPost)
        {
            return new RedirectResponse($this->urlGenerator->generate('index'));        
        }
        $tags = $this->entityManager->getRepository(Tags::class)->findBy(['post'=> $blogPost->getId()]);
        
        //komentare k clanku
        $comments = $this->blogComments($blogPost->getId());
        
        //formular na novy komentar
        $formComment = $this->createForm(CommentFormType::class, $blogPost,[],[
            'method' => 'POST',
        ]); 

        return $this->render('blog/post.html.twig', [
            'post'     => $blogPost,
            'postTags' => $tags,
            'comments' => $comments,
            'formComment' => $formComment,
        ]);
    }
    
    //Zobrazenie autora
    #[Route('/autor/{id}',name:'author') ]
    public function blogAuthor(int $id = null)  
    {   
        if(!$id)
        {
            return new RedirectResponse($this->urlGenerator->generate('index'));        
        }
        $author = $this->entityManager->getRepository(User::class)->find($id);
        if(!$author)
        {
            return new RedirectResponse($this->urlGenerator->generate('index'));        
        }
        $blogPosts = $this->entityManager->getRepository(BlogPosts::class)->findBy(['author'=>$author->getId()]);
        if(!$blogPosts)
        {
            return new RedirectResponse($this->urlGenerator->generate('index'));        
        }
        $postTags = array();
         
        foreach($blogPosts as $post)
        {
            $tag[$post->getId()] = $this->entityManager->getRepository(Tags::class)->findBy(['post'=> $post->getId()]);
            $tagArray = array();    
            foreach($tag[$post->getId()] as $tagWord)
            {   
                $tagArray[] = $tagWord->getTag();
                $postTags[$post->getid()] = $tagArray;
            }
        }

        return $this->render('blog/author.html.twig', [
             'author'     => $author,
             'posts'      => $blogPosts,
             'postTags'   => $postTags,
        ]); 

    }

    //Admin prostredie blogu
    #[Route('/settings',name:'settings') ]
    public function blogSettingsPanel()  
    {   
        $posts = $this->entityManager->getRepository(BlogPosts::class)->findBy(['author'=>$this->getUser()->getId()],['date'=>'DESC']);
        $postTags = array();

        foreach($posts as $post)
        {
            $tag[$post->getId()] = $this->entityManager->getRepository(Tags::class)->findBy(['post'=> $post->getId()]);
            $tagArray = array();
            foreach($tag[$post->getId()] as $tagWord)
            {   
                $tagArray[] = $tagWord->getTag();
                $postTags[$post->getid()] = $tagArray;
            }
        }
        return $this->render('blog/admin/admin-index.html.twig', [
             'posts' => $posts,
             'postTags' => $postTags,
        ]);
    }

    //Pridávanie nových článkov pre prihlásených užívateľov.
    #[Route('/settings/new', name:'new_blog')]
    public function addNewBlog(Request $request  ) : Response
    {   
        $blogPost = new BlogPosts();
        $form = $this->createForm(AddNewBlogPostFormType::class, $blogPost,[], [
            'method' => 'POST',
        ]); 
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $blogPost->setAuthor($this->getUser());
            
            $tags = explode(",", $form['tags']->getData()); //Pole rozdelene podla ,
            $tags = array_unique($tags); //odstranenie duplicit 
            foreach ($tags as $outerTag) {
                $innerTag = explode(" ", $outerTag); //Pole rozdelene podla medzery
                foreach ($innerTag as $tag) {
                    if ($tag <> "") {
                        $tag = str_replace('"', '', $tag);
                        $tag = str_replace("'", '', $tag);
                        $Tags = new Tags();
                        $Tags->setPost($blogPost);
                        $Tags->setTag(trim($tag));
                        $this->entityManager->persist($Tags);
                    }
                }
            }

            $photoFile = $form->get('photo')->getData();  
            if ($photoFile) {
                $newFilename = $this->upload($photoFile);
                $blogPost->setPhoto($newFilename);
            }      

            $this->entityManager->persist($blogPost);
            $this->entityManager->flush();
            $this->addFlash('success', 'Článok úspešne pridaný');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }

        return $this->render(
            '/blog/admin/form/blogForm.html.twig',
                array('form' => $form->createView(),
                'type' => 'new'
            )
        );
    }

    //Uprava clanku
    #[Route('/settings/edit/{id}',name:'settings_edit_post') ]
    public function blogSettingsPanelEdit(Request $request,int $id = null)  : Response
    {   
        if(!$id)
        {   
            $this->addFlash('danger', 'Článok neexistuje');
            return new RedirectResponse($this->urlGenerator->generate('settings'));        
        }
        
        $blogPost = $this->entityManager->getRepository(BlogPosts::class)->find($id);
        if(!$blogPost){
            $this->addFlash('danger', 'Článok neexistuje');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }
        if(!($blogPost->getAuthor()->getId() == $this->getUser()->getId()))
        {   
            $this->addFlash('danger', 'Článok patrí inému používateľovi');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }
        if($blogPost->getPhoto()) //Povodna fotka clanku
        {  
            $oldPhoto = $blogPost->getPhoto(); 
        }else{
            $oldPhoto = null;
        }
        
        $form = $this->createForm(AddNewBlogPostFormType::class, $blogPost,[], [
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            foreach ($this->entityManager->getRepository(Tags::class)->findBy(['post' => $id]) as $oldTag) {
                $this->entityManager->remove($oldTag);
            }
            $tags = explode(",", $form['tags']->getData());
            $tags = array_unique($tags);
            foreach ($tags as $outerTag) {
                $innerTag = explode(" ", $outerTag);
                foreach ($innerTag as $tag) {
                    if ($tag <> "") {
                        $tag = str_replace('"', '', $tag);
                        $tag = str_replace("'", '', $tag);
                        $Tags = new Tags();
                        $Tags->setPost($blogPost);
                        $Tags->setTag(trim($tag));
                        $this->entityManager->persist($Tags);
                    }
                }
            }

            $photoFile = $form->get('photo')->getData();  
            if ($photoFile) { //Ak uzivatel nahra novu fotku, uploadne sa a nahradi sa za staru fotku
                $newFilename = $this->upload($photoFile);
                $blogPost->setPhoto($newFilename);
                if($oldPhoto)
                {
                    $this->removeOldPostPhoto($oldPhoto); //Stara fotka sa vymaze zo servera
                }
            }else{
                $blogPost->setPhoto($oldPhoto); //Ak uzivatel nenahra novu fotku, ostava stara
            }      

            $this->entityManager->persist($blogPost);
            $this->entityManager->flush();
            $this->addFlash('success', 'Článok úspešne aktualizovaný');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }
        
        return $this->render(
            '/blog/admin/form/blogForm.html.twig',
                array('form' => $form->createView(),
                'type' => 'edit',
                'post' => $blogPost,
            )
        );     
    }

    //Mazanie clanku
    #[Route('/settings/delete/{id}',name:'settings_delete_post') ]
    public function blogSettingsPanelDelete(Request $request,int $id = null)  : Response
    {   
        if(!$id)
        {   
            $this->addFlash('danger', 'Článok neexistuje');
            return new RedirectResponse($this->urlGenerator->generate('settings'));        
        }
        
        $blogPost = $this->entityManager->getRepository(BlogPosts::class)->find($id);
        if(!$blogPost){
            $this->addFlash('danger', 'Článok neexistuje');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }
        if(!($blogPost->getAuthor()->getId() == $this->getUser()->getId()))
        {   
            $this->addFlash('danger', 'Článok patrí inému používateľovi');
            return new RedirectResponse($this->urlGenerator->generate('settings'));      
        }
        //Zmazanie tagov
        foreach ($this->entityManager->getRepository(Tags::class)->findBy(['post' => $id]) as $tag) {
            $this->entityManager->remove($tag);
        }
        //Zmazanie komentarov
        foreach ($this->entityManager->getRepository(Comments::class)->findBy(['post' => $id],['id'=>'desc']) as $comment) {
            $this->entityManager->remove($comment);
        }
        //Zmazanie fotky zo servera
        if ($blogPost->getPhoto()) {
            $this->removeOldPostPhoto($blogPost->getPhoto());
        }    

        $this->entityManager->remove($blogPost);
        $this->entityManager->flush();
        $this->addFlash('success', 'Článok zmazaný');
        return new RedirectResponse($this->urlGenerator->generate('settings'));      
    }


    //Uprava uzivatelskeho profilu
    #[Route('/profile',name:'user_profile') ]
    public function blogUserSettings(Request $request) :Response
    {   
        if(!$this->getUser())
        {   
            return new RedirectResponse($this->urlGenerator->generate('index'));
        }
        $user = $this->entityManager->getRepository(User::class)->find($this->getUser()->getId());
        
        $form = $this->createForm(UserFormType::class, $user,[], [
            'method' => 'POST',
        ]); 

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Profil upravený');
        }
        return $this->render('/blog/admin/user-settings.html.twig',
            array('form' => $form->createView(), )
        );  
         
    }
    
    //Zobrazenie tagoveho oblaku
    #[Route('/tagcloud',name:'tag_cloud') ]
    public function blogTagCloud()
    {   
        $tags = $this->entityManager->getRepository(Tags::class)->findAll();
        if(!$tags)
        {
            return $this->render('/blog/tagcloud.html.twig',
            array('tagcloud' => null, )
            );
        }
        $inputTags = [];
        foreach($tags as $tag)
        {
            $inputTags[] = $tag->getTag();
        }
        shuffle($inputTags); //Nahodne nastavi poradie prvkov v poli
        $countValues = [];
 
        foreach($inputTags as $tag)
        {   
            @$countValues[$tag]++; //Spocita rovnake hodnoty pola
        }
         
        $maxFontSize = 72;
        $minFontSize = 16;

        $highestOccurence = max(array_values($countValues)); //Najvacsia hodnota pola
    	$lowestOccurence = min(array_values($countValues)); //Najmensia hodnota pola    

        $spread = $highestOccurence - $lowestOccurence; //Rozsah hodnot
        if($spread == 0){
            $spread = 1;
        }
        
        $step = ($maxFontSize - $minFontSize)/$spread;  
        
        $tagLinks = [];
        foreach($countValues as $key => $val)
        {
            $size = $minFontSize + (($val - $lowestOccurence)* $step); //vypocet velkosti pisma
            $tagLinks[] =  '  <a href="index?search_blog_form[tags]='.$key.'" style="font-size: '.$size.'px" class="text-primary" >'.$key.'</a> ';
        }
       
        return $this->render('/blog/tagcloud.html.twig',
            array('tagcloud' => $tagLinks, )
        );  
         
    }

    //pridavanie komentaru k clanku
    #[Route('/api/addnewcommenttopost',name:'add_new_comment_to_post',methods: ['POST']) ]
    public function addNewCommentToPost(Request $request, ) :Response
    {   
        $form = $request->request->all();
         
        if(!$form['comment_form']){
            $jsonData['status'] = 404;
            $jsonData['message'] = 'Niekde sa stala chyba, akciu opakuj neskôr';
            return new JsonResponse($jsonData); 
        }
        if(!$form['comment_form']['post'] || $form['comment_form']['post'] == '' || $form['comment_form']['post'] == null)
        {
            $jsonData['status'] = 404;
            $jsonData['message'] = 'Niekde sa stala chyba, akciu opakuj neskôr';
            return new JsonResponse($jsonData); 
        }
        
        if(!$form['comment_form']['comment'] || $form['comment_form']['comment'] == '' || $form['comment_form']['comment'] == null)
        {
            $jsonData['status'] = 404;
            $jsonData['message'] = 'Zadaj komentár';
            return new JsonResponse($jsonData); 
        }

        $post = $this->entityManager->getRepository(BlogPosts::class)->findOneBy(['id'=>$form['comment_form']['post']]);
        if(!$post)
        {
            $jsonData['status'] = 404;
            $jsonData['message'] = 'Niekde sa stala chyba, akciu opakuj neskôr';
            return new JsonResponse($jsonData); 
        }
        
        $comment = new Comments();
        $comment->setComment($form['comment_form']['comment'])
                ->setPost($post)
                ->setDate(new \Datetime('now'));
                 
        if($this->security->getUser()){
            $comment->setUser($this->security->getUser());
        }
        if($form['comment_form']['parent']){
            $parentComment = $this->entityManager->getRepository(Comments::class)->findOneBy(['id'=>$form['comment_form']['parent']]);
            if(!$parentComment)
            {   $jsonData['status'] = 404;
                $jsonData['message'] = 'Niekde sa stala chyba, akciu opakuj neskôr';
                
            }
            $comment->setParent($parentComment);
        }
         
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
        $jsonData['status'] = 200;
        $jsonData['message'] = 'Komentár pridaný';
        $jsonData['comment']['id'] = $comment->getId();
        $jsonData['comment']['text'] = $comment->getComment();
        
        $jsonData['comment']['date'] = $comment->getDate()->format('d.m.Y H:i');
        if($this->security->getUser())
        {
            $jsonData['comment']['user'] = $comment->getUser()->getEmail();
        }else{
            $jsonData['comment']['user'] = 'Anonym';
        }
        return new JsonResponse($jsonData); 
    }
     
    //Komentare ku konkretnemu clanku
    private function blogComments($postId)
    {   
        $comments = $this->entityManager->getRepository(Comments::class)->findBy(['post'=>$postId],['date'=>'asc']);
        $html     = [];
        $rootId   = 0;
        foreach($comments as $comment)
        {   
            if($comment->getParent()){ //koment ma nadradeny koment (je odpovedou)
                $children[$comment->getParent()->getId()][] = $comment;
            }else{
                $children[0][] = $comment; //koment je korenom
            }
        }
        
        $loop = !empty($children[$rootId]); //ak existuje korenovy komentar false
       
        $parent = $rootId; //parent = 0
          
        $parentStack = [];
        $html[] = '<ul id="commentsListHolder">';
        while ($loop && ( ( $option = $this->eachCommentOption($children[$parent]) ) || ( $parent > $rootId ) )) {
            //dump($option);
            if ($option === false) { //uzatvara koment obsahujuci odpovede, kde uz nie su dalsie prvky v poli
                
				$parent = array_pop($parentStack);
                $html[] = '</ul>';
				$html[] = '</li>';
			}
            elseif (!empty($children[$option['value']->getId()]))//odpovede
            {
				$depthLevel = count($parentStack);
				if ($depthLevel <= 3) {
					$reply_link = '<button class="reply-button btn btn-outline-primary btn-sm pb-2 border-0" id="%1$s">Odpovedať</button>';
				} else {
					$reply_link = '';
				}
				
				$html[] = sprintf(
						'<li id="li_comment_%1$s" data-depth-level="' . $depthLevel . '">' .
						'<div><span class="comment-user">%2$s</span> <span class="comment-date">%3$s</span></div>' .
						'<div style="margin-top:4px;">%4$s</div>' .
						$reply_link . '</li>'
                        ,   
						$option['value']->getId(),  
                        ( ($option['value']->getUser() === null) ? 'Anonym' : $option['value']->getUser()->getEmail()),
                        $option['value']->getDate()->format('d.m.Y H:i'),
						$option['value']->getComment(),  
				);
				 
				$html[] = '<ul class="comment">';
                if($option['value']->getParent())
                {
                    array_push($parentStack, $option['value']->getParent()->getId());
                }else{
                    array_push($parentStack, 0);
                }
				
				$parent = $option['value']->getId();
			}else //komentare bez odpovedi
            {
				$depthLevel = count($parentStack);
				if ($depthLevel <= 3) {
					$reply_link = '<button class="reply-button btn btn-outline-primary btn-sm pb-2 border-0" id="%1$s">Odpovedať</button>';
				} else {
					$reply_link = '';   
				}
				$html[] = sprintf(
						'<li id="li_comment_%1$s" data-depth-level="'.$depthLevel.'">' .
						    '<div><span class="comment-user">%2$s</span> <span class="comment-date">%3$s</span></div>' .
						    '<div style="margin-top:4px;">%4$s</div>'.$reply_link.'</li>'
                        ,  
						$option['value']->getId(),  
                        ( ($option['value']->getUser() === null) ? 'Anonym' : $option['value']->getUser()->getEmail()),
                        $option['value']->getDate()->format('d.m.Y H:i'),
						$option['value']->getComment(),
				);
			} 
        }
        
        $html[] = '</ul>';

        return implode($html);
    }

    public function eachCommentOption(&$arr) { //each
        $key = key($arr);//Ak uz nie je dalsi prvok v poli, vrati false
        if($key === null)
        {
            $result = false;
        }else{
            // aktualny kluc a hodnota pola
            $result = [$key, current($arr), 'key' => $key, 'value' => current($arr)];
        }
        next($arr); //presun na dalsi prvok v poli
        return $result;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('images'), $fileName);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Fotku sa nepodarilo nahrať');
        }

        return $fileName;
    }
    
    private function removeOldPostPhoto($oldPhoto){     
        try {        
            $filesystem = new FileSystem();    
            if(file_exists($this->getParameter('images').'/'.$oldPhoto)){
                $filesystem->remove($this->getParameter('images').'/'.$oldPhoto);
            }
            return true;
        }catch(Exception  $e){
            return false;
        }
    }
 
}


