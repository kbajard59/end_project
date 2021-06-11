<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class RegisterController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $notification = null;
        $user = new User();
        $form = $this->createForm(RegisterType::class,$user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $user = $form->getData();

            $search_mail = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());

            if(!$search_mail){
                $password = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($password);

                $this->entityManager->persist($user);
                $this->entityManager->flush();
                //Envoi du mail
                $mail = new Mail();
                $content = "Bonjour ".$user->getFirstName().",<br/>Bienvenue sur Burger Queen.<br/><br/>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Beatae error esse id molestiae odio provident quasi quisquam quos, reiciendis saepe!";
                $mail->send($user->getEmail(),$user->getFirstName(),'Bienvenue sur La Boutique Française',$content);
                $notification = "Votre inscription s'est correctement déroulée. Vous pouvez dès à présent vous connectez à votre compte.";
            }else{
                $notification = "L'email que vous avez renseigné existe déjà.";
            }
        }

        return $this->render('register/index.html.twig',[
            'form'=>$form->createView(),
            'notification' => $notification
        ]);
    }
}
