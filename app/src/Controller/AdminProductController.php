<?php

namespace App\Controller;


use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProductType;

final class AdminProductController extends AbstractController
{
    #[Route('/admin/products', name: 'app_admin_product_list')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin_product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }
     #[Route('/admin/products/delete/{id}', name: 'admin_product_delete')]
     public function delete(Product $product, EntityManagerInterface $em): Response
    {
        
     foreach ($product->getImages() as $image) {
         $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $image->getPath();
             if (file_exists($filePath)) {
            unlink($filePath); 
        }
        $em->remove($image);
    }
             $em->remove($product);
             $em->flush();
                $this->addFlash('success', 'Produit supprimé avec succès.');

         return $this->redirectToRoute('app_admin_product_list');
    }

    #[Route('/admin/products/add', name: 'admin_product_add')]
    public function add( Request $request, EntityManagerInterface $em):Response
    {
            $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $product= $form->getData();            
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', "L'objet a été ajouté avec succès.");

            return $this->redirectToRoute('app_admin_product_list');
        }
        
        return $this->render('admin_product/form.html.twig', [
            'form' => $form->createView()
        ]);
        
        }

}
