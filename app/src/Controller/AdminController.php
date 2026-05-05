<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminController extends AbstractController
{
   #[Route('/admin/product', name: 'admin_product_index')]
   #[IsGranted('ROLE_ADMIN')]
public function index(): Response
    {
       return $this->render('admin/index.html.twig');
    }


}
