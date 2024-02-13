<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Filter\UserFilter;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminUserController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'user';
    CONST ENTITY_NAME = 'User';
    CONST NS_ENTITY_NAME = 'App:User';

    /**
     * Lists all user entities.
     *
     * @Route("backend/user/index", name="backend_user_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, UserFilter::class);

        return $this->render('admin/user/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'user.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'user.name',
                    'sortable' => true,
                ],
                'a.email' => [
                    'title' => 'Email',
                    'row_field' => 'email',
                    'sorting_field' => 'user.email',
                    'sortable' => true,
                ],
                'a.phone' => [
                    'title' => 'Phone',
                    'row_field' => 'phone',
                    'sorting_field' => 'user.phone',
                    'sortable' => true,
                ],
                'a.isVerified' => [
                    'title' => 'Verified?',
                    'row_field' => 'isVerified',
                    'sorting_field' => 'user.isVerified',
                    'sortable' => false,
                ],
                'a.isActive' => [
                    'title' => 'Active?',
                    'row_field' => 'isActive',
                    'sorting_field' => 'user.isActive',
                    'sortable' => false,
                ],
            ]
        ));
    }


    /**
     * Creates a new user entity.
     *
     * @Route("backend/user/new", name="backend_user_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, UserRepository $repository, EntityManagerInterface $em)
    {
        $user = new User();
        $form = $this->createForm('App\Form\UserType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setSlug($this->makeSlug($user, $repository));

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_user_edit', array('id' => $user->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $user,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(User $user, UserRepository $repository)
    {
        $slug = $user->getSlug() ?? Slugger::urlSlug($user->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("backend/user/{id}", name="backend_user_show", methods={"GET"})
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('admin/common/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("backend/user/{id}/edit", name="backend_user_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, User $user, FileUploader $fileUploader)
    {
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm(UserType::class, $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_user_edit', array('id' => $user->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/user/edit.html.twig', array(
            'row' => $user,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("backend/user/{id}", name="backend_user_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, User $user)
    {
        $filter_form = $this->createDeleteForm($user);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
