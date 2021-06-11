<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountPasswordController extends AbstractController
{
    private $passwordEncoder;
    private $entityManager;

    public function __construct(UserPasswordEncoderInterface $encoder,EntityManagerInterface $entityManager){

        $this->passwordEncoder = $encoder;
        $this->entityManager=$entityManager;
    }
    /**
     * @Route("/compte/modifier-mot-de-passe", name="account_password")
     */
    public function index(Request $request)
    {
        $notification = null;
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class,$user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $old_pw = $form->get('old_password')->getData();


            if($this->passwordEncoder->isPasswordValid($user,$old_pw)){
                $new_pw = $form->get('new_password')->getData();
                $password = $this->passwordEncoder->encodePassword($user,$new_pw);

                $user->setPassword($password);

                $this->entityManager->flush();
                // mail
                //Envoi du mail
                $mail = new Mail();
                $content = "Bonjour ".$user->getFirstName().",<br/>Le mot de passe de votre compte Burger Queen a bien été modifié.<br/><br/>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Beatae error esse id molestiae odio provident quasi quisquam quos, reiciendis saepe!";
                $mail->send($user->getEmail(),$user->getFirstName(),'Modification de votre mot de passe - Burger Queen',$content);
                $notification = "Votre mot de passe a bien été mis à jour.";
            }else{
                $notification = "Le mot de passe actuel n'est pas le bon.";
            }

        }

        return $this->render('account/password.html.twig',[
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
