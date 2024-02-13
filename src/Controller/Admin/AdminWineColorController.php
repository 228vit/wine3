<?php

namespace App\Controller\Admin;

use App\Entity\Alias;
use App\Entity\WineColor;
use App\Filter\WineColorFilter;
use App\Form\WineColorType;
use App\Repository\AliasRepository;
use App\Repository\WineColorRepository;
use App\Service\FileUploader;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminWineColorController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'wine_color';
    CONST ENTITY_NAME = 'WineColor';
    CONST NS_ENTITY_NAME = 'App:WineColor';

    /**
     * Lists all wineColor entities.
     *
     * @Route("backend/wine_color/index", name="backend_wine_color_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, WineColorFilter::class,
            'name', 'ASC');


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
                    'sorting_field' => 'wine_color.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'wine_color.name',
                    'sortable' => true,
                ],
                'a.engName' => [
                    'title' => 'Eng. name',
                    'row_field' => 'engName',
                    'sorting_field' => 'wine_color.engName',
                    'sortable' => true,
                ],
                'a.position' => [
                    'title' => 'Position',
                    'row_field' => 'position',
                    'sorting_field' => 'wine_color.position',
                    'sortable' => true,
                ],
                'a.aliasesAsString' => [
                    'title' => 'Aliases',
                    'row_field' => 'aliasesAsString',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new wineColor entity.
     *
     * @Route("backend/wine_color/new", name="backend_wine_color_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $wineColor = new WineColor();
        $form = $this->createForm(WineColorType::class, $wineColor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($wineColor);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_wine_color_edit', array('id' => $wineColor->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'wineColor' => $wineColor,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a wineColor entity.
     *
     * @Route("backend/wine_color/{id}", name="backend_wine_color_show", methods={"GET"})
     */
    public function showAction(WineColor $wineColor)
    {
        $deleteForm = $this->createDeleteForm($wineColor);

        return $this->render('admin/common/show.html.twig', array(
            'wineColor' => $wineColor,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing wineColor entity.
     *
     * @Route("backend/wine_color/{id}/edit", name="backend_wine_color_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request,
                               WineColor $wineColor,
                               WineColorRepository $wineColorRepository,
                               AliasRepository $aliasRepository)
    {
        $editForm = $this->createForm(WineColorType::class, $wineColor);
        $editForm->handleRequest($request);

        $dbAliases = $aliasRepository->findBy([
            'modelName' => self::MODEL,
            'name' => $wineColor->getName(),
        ]);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->persist($wineColor);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_wine_color_edit', array('id' => $wineColor->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($wineColor);

        return $this->render('admin/wine_color/edit.html.twig', array(
            'row' => $wineColor,
            'aliases' => $dbAliases,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a wineColor entity.
     *
     * @Route("backend/wine_color/{id}", name="backend_wine_color_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, WineColor $wineColor, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($wineColor);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $this->em->remove($wineColor);
            $this->em->flush($wineColor);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_wine_color_index');
    }

    /**
     * Creates a form to delete a wineColor entity.
     */
    private function createDeleteForm(WineColor $wineColor) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_wine_color_delete', array('id' => $wineColor->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
