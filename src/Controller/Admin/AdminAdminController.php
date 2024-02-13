<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Filter\AdminFilter;
use App\Form\AdminNewType;
use App\Form\AdminType;
use App\Repository\AdminRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class AdminAdminController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'admin';
    CONST ENTITY_NAME = 'Admin';
    CONST NS_ENTITY_NAME = 'App:Admin';

    /**
     * Lists all admin entities.
     *
     * @Route("backend/admin/index", name="backend_admin_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, AdminFilter::class);

        return $this->render('admin/common/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'admin.id',
                    'sortable' => true,
                ],
                'a.email' => [
                    'title' => 'Email',
                    'row_field' => 'email',
                    'sorting_field' => 'admin.email',
                    'sortable' => true,
                ],
                'a.isSuperAdmin' => [
                    'title' => 'SuperAdmin?',
                    'row_field' => 'isSuperAdmin',
                    'sorting_field' => 'admin.isSuperAdmin',
                    'sortable' => false,
                ],
                'a.isEditor' => [
                    'title' => 'Editor?',
                    'row_field' => 'isEditor',
                    'sorting_field' => 'admin.isEditor',
                    'sortable' => false,
                ],
            ]
        ));
    }


    /**
     * Displays a form to edit an existing admin entity.
     *
     * @Route("backend/admin/{id}/edit", name="backend_admin_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Admin $admin, FileUploader $fileUploader)
    {
        $deleteForm = $this->createDeleteForm($admin);
        $editForm = $this->createForm(AdminType::class, $admin);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->persist($admin);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_admin_edit', array('id' => $admin->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/common/edit.html.twig', array(
            'row' => $admin,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Creates a new admin entity.
     *
     * @Route("backend/admin/new", name="backend_admin_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request,
                              AdminRepository $repository,
                              UserPasswordEncoderInterface $passwordEncoder)
    {
        $admin = new Admin();
        $form = $this->createForm(AdminNewType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $password = $passwordEncoder->encodePassword($admin, $admin->getPassword());
            $admin->setPassword($password);

            $this->em->persist($admin);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_admin_edit', array('id' => $admin->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $admin,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Admin $admin, AdminRepository $repository)
    {
        $slug = $admin->getSlug() ?? Slugger::urlSlug($admin->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a admin entity.
     *
     * @Route("backend/admin/{id}", name="backend_admin_show", methods={"GET"})
     */
    public function showAction(Admin $admin)
    {
        $deleteForm = $this->createDeleteForm($admin);

        return $this->render('admin/common/show.html.twig', array(
            'admin' => $admin,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a admin entity.
     *
     * @Route("backend/admin/{id}", name="backend_admin_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Admin $admin)
    {
        $this->addFlash('alert', 'Admin cannot be deleted!');

        return $this->redirectToRoute('backend_admin_index');
    }

    /**
     * Creates a form to delete a admin entity.
     *
     * @param Admin $admin The admin entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Admin $admin)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_admin_delete', array('id' => $admin->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
