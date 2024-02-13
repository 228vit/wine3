<?php

namespace App\Controller\Admin;

use App\Repository\AdminRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_dashboard")
     */
    public function admin(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'controller_name' => 'AdminDashboardController',
        ]);
    }

    /**
     * @Route("/backend", name="backend_dashboard")
     */
    public function index(ProductRepository $productRepository, AdminRepository $adminRepository): Response
    {
        $output = [];
        $editors = [];
        foreach ($adminRepository->findBy(['isEditor' => true]) as $editor) {
            $editors[$editor->getId()] = $editor->getEmail();
        }

        $editedDates = [];
        /** @var \DateTime $editedDate */
        foreach ($productRepository->getEditedDates() as $editedDate) {
            $updated = $editedDate['updatedAt']->format('d.m.Y');
            $editedDates[$updated] = $updated;
        }

        $editorsWork = [];
        foreach ($productRepository->countEditedByDate() as $row) {
            $updated = $row['updatedAt']->format('d.m.Y');
            // editorsWork[2020-01-01][1] = 10;
            @$editorsWork[$updated][$row['editor_id']] += $row['cnt'];
        }


        return $this->render('admin/dashboard.html.twig', [
            'editedDates' => $editedDates,
            'editorsWork' => $editorsWork,
            'editors' => $editors,
        ]);
    }

}
