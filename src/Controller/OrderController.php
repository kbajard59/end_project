<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(entityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="order")
     */
    public function index(Request $request,Cart $cart): Response
    {
        if(!$this->getUser()->getAddresses()->getValues()){
            // Si il n'y a pas d'adresse alors redirection vers page de création d'adresse
            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class,null, [
            'user' =>$this->getUser()
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            dd($form->getData());
        }

        return $this->render('order/index.html.twig', [
            'form' =>$form->createView(),
            'cart' => $cart->getFull()
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     */
    public function add(Request $request,Cart $cart): Response
    {
        $form = $this->createForm(OrderType::class,null, [
            'user' =>$this->getUser()
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $date = new DateTime();
            $delivery_mode = $form->get('delivery')->getData();
            $delivery_address = $form->get('addresses')->getData();
            $delivery_content = $delivery_address->getFirstname().' '.$delivery_address->getLastname();
            $delivery_content .= '<br/>'.$delivery_address->getPhone();
            if($delivery_address->getCompany()){
                $delivery_content .= '<br/>'.$delivery_address->getCompany();
            }
            $delivery_content .= '<br/>'.$delivery_address->getAddress();
            $delivery_content .= '<br/>'.$delivery_address->getPostal().' '.$delivery_address->getCity();
            $delivery_content .= '<br/>'.$delivery_address->getCountry();

            //enregistrer mes commandes Order()
            $order = new Order();
            /*$reference = $date->format('dmY').'-'.uniqid();
            $order->setReference($reference);*/
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setDeliveryName($delivery_mode->getName());
            $order->setDeliveryPrice($delivery_mode->getPrice());
            $order->setDeliveryAddress($delivery_content);
            $order->setIsPaid(0);
            /*$order->setState(0);*/

            $this->entityManager->persist($order);


            //enregistrer mes produits OrderDetails()
            foreach ($cart->getFull() as $p){
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($p['product']->getName());
                $orderDetails->setQuantity($p['quantity']);
                $orderDetails->setPrice($p['product']->getPrice());
                $orderDetails->settotal($p['product']->getPrice() * $p['quantity']);
                $this->entityManager->persist($orderDetails);
            }

            $this->entityManager->flush();


            return $this->render('order/add.html.twig', [
                'cart' => $cart->getFull(),
                'carrier' =>$delivery_mode,
                'delivery' =>$delivery_content/*,
                'reference' =>$order->getReference()*/
            ]);
        }

        return $this->redirectToRoute('cart');
    }
}
