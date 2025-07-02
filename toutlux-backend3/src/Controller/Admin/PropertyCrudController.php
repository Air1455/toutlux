<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Entity\PropertyImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PropertyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Property::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Property')
            ->setEntityLabelInPlural('Properties')
            ->setPageTitle('index', 'Property Management')
            ->setPageTitle('detail', fn (Property $property) => $property->getTitle())
            ->setPageTitle('edit', fn (Property $property) => sprintf('Edit: %s', $property->getTitle()))
            ->setSearchFields(['title', 'description', 'address', 'city', 'zipCode'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setFormThemes(['admin/form/property_images.html.twig', '@EasyAdmin/crud/form_theme.html.twig']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOnSite = Action::new('viewOnSite', 'View on Site', 'fa fa-external-link-alt')
            ->linkToUrl(fn (Property $property) => '/properties/' . $property->getId())
            ->setHtmlAttributes(['target' => '_blank']);

        $manageImages = Action::new('manageImages', 'Manage Images', 'fa fa-images')
            ->linkToCrudAction('manageImages');

        $viewStats = Action::new('viewStats', 'Statistics', 'fa fa-chart-line')
            ->linkToCrudAction('viewStats');

        $duplicate = Action::new('duplicate', 'Duplicate', 'fa fa-copy')
            ->linkToCrudAction('duplicate')
            ->setCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewOnSite)
            ->add(Crud::PAGE_INDEX, $viewStats)
            ->add(Crud::PAGE_DETAIL, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $manageImages)
            ->add(Crud::PAGE_DETAIL, $viewStats)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus-circle')->setLabel('Add Property');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('title')
                ->setColumns(8),
            TextareaField::new('description')
                ->hideOnIndex()
                ->setColumns(12),
            ChoiceField::new('type')
                ->setChoices([
                    'For Sale' => Property::TYPE_SALE,
                    'For Rent' => Property::TYPE_RENT
                ])
                ->renderAsBadges([
                    Property::TYPE_SALE => 'success',
                    Property::TYPE_RENT => 'info'
                ])
                ->setColumns(4),
            MoneyField::new('price')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setColumns(4),
            NumberField::new('surface')
                ->setNumDecimals(2)
                ->setSuffix(' m²')
                ->setColumns(4),
            IntegerField::new('rooms')
                ->setColumns(3),
            IntegerField::new('bedrooms')
                ->setColumns(3),
            TextField::new('address')
                ->hideOnIndex()
                ->setColumns(6),
            TextField::new('city')
                ->setColumns(4),
            TextField::new('zipCode')
                ->setColumns(2),
        ];

        if ($pageName !== Crud::PAGE_NEW) {
            $fields[] = ChoiceField::new('status')
                ->setChoices([
                    'Available' => Property::STATUS_AVAILABLE,
                    'Sold' => Property::STATUS_SOLD,
                    'Rented' => Property::STATUS_RENTED
                ])
                ->renderAsBadges([
                    Property::STATUS_AVAILABLE => 'primary',
                    Property::STATUS_SOLD => 'danger',
                    Property::STATUS_RENTED => 'warning'
                ])
                ->setColumns(4);
        }

        $fields[] = ArrayField::new('features')
            ->hideOnIndex()
            ->setColumns(12);

        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_INDEX) {
            $fields[] = AssociationField::new('owner')
                ->setColumns(4);
            $fields[] = IntegerField::new('viewCount')
                ->setColumns(2);
            $fields[] = DateTimeField::new('createdAt')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->setColumns(3);
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = DateTimeField::new('updatedAt')
                ->setFormat('dd/MM/yyyy HH:mm');
            $fields[] = NumberField::new('latitude')
                ->setNumDecimals(6);
            $fields[] = NumberField::new('longitude')
                ->setNumDecimals(6);
            $fields[] = CollectionField::new('images')
                ->setTemplatePath('admin/fields/property_images.html.twig');
            $fields[] = CollectionField::new('messages')
                ->setTemplatePath('admin/fields/property_messages.html.twig');
        }

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            $fields[] = AssociationField::new('owner')
                ->setRequired(true)
                ->setColumns(4);
        }

        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = ImageField::new('mainImageUrl', 'Image')
                ->setBasePath('')
                ->onlyOnIndex();
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices([
                'For Sale' => Property::TYPE_SALE,
                'For Rent' => Property::TYPE_RENT
            ]))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Available' => Property::STATUS_AVAILABLE,
                'Sold' => Property::STATUS_SOLD,
                'Rented' => Property::STATUS_RENTED
            ]))
            ->add(TextFilter::new('city'))
            ->add(TextFilter::new('zipCode'))
            ->add(NumericFilter::new('price'))
            ->add(NumericFilter::new('surface'))
            ->add(NumericFilter::new('rooms'))
            ->add(NumericFilter::new('bedrooms'))
            ->add(DateTimeFilter::new('createdAt'));
    }

    public function manageImages(AdminContext $context): Response
    {
        $property = $context->getEntity()->getInstance();

        if ($this->isPostRequest()) {
            // Handle image upload
            $uploadedFiles = $this->getRequest()->files->get('images');

            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $image = new PropertyImage();
                    $image->setImageFile($uploadedFile);
                    $image->setProperty($property);
                    $image->setPosition(count($property->getImages()));

                    $this->getDoctrine()->getManager()->persist($image);
                }

                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Images uploaded successfully');
            }

            return $this->redirectToRoute('admin', [
                'crudAction' => 'manageImages',
                'crudControllerFqcn' => self::class,
                'entityId' => $property->getId()
            ]);
        }

        return $this->render('admin/property/manage_images.html.twig', [
            'property' => $property,
            'images' => $property->getImages()
        ]);
    }

    public function viewStats(AdminContext $context): Response
    {
        $property = $context->getEntity()->getInstance();

        // Get property statistics
        $stats = $this->getPropertyStats($property);

        return $this->render('admin/property/stats.html.twig', [
            'property' => $property,
            'stats' => $stats
        ]);
    }

    public function duplicate(AdminContext $context): Response
    {
        $property = $context->getEntity()->getInstance();

        $newProperty = clone $property;
        $newProperty->setTitle($property->getTitle() . ' (Copy)');
        $newProperty->setStatus(Property::STATUS_AVAILABLE);
        $newProperty->setViewCount(0);

        $this->getDoctrine()->getManager()->persist($newProperty);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Property duplicated successfully');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'edit',
            'crudControllerFqcn' => self::class,
            'entityId' => $newProperty->getId()
        ]);
    }

    private function getPropertyStats(Property $property): array
    {
        $em = $this->getDoctrine()->getManager();

        // Get view statistics
        $viewsLastWeek = $this->getViewsForPeriod($property, '-1 week');
        $viewsLastMonth = $this->getViewsForPeriod($property, '-1 month');

        // Get message statistics
        $totalMessages = count($property->getMessages());
        $pendingMessages = $property->getMessages()->filter(
            fn($message) => $message->getStatus() === 'pending'
        )->count();

        return [
            'total_views' => $property->getViewCount(),
            'views_last_week' => $viewsLastWeek,
            'views_last_month' => $viewsLastMonth,
            'total_messages' => $totalMessages,
            'pending_messages' => $pendingMessages,
            'days_on_market' => $property->getCreatedAt()->diff(new \DateTime())->days
        ];
    }

    private function getViewsForPeriod(Property $property, string $period): int
    {
        // This is a placeholder - you would implement actual view tracking
        return rand(10, 100);
    }

    private function isPostRequest(): bool
    {
        return $this->container->get('request_stack')->getCurrentRequest()->isMethod('POST');
    }

    private function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    private function getDoctrine()
    {
        return $this->container->get('doctrine');
    }
}
