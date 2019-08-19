<?php


namespace App\Controller\Ecorp;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomepageController extends AbstractController
{
    public function home(): Response
    {
        return $this->render('homepage.html.twig', []);
    }

}