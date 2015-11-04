<?php
namespace Presentation\Grids\Demo;

use Presentation\Framework\Component\ManagedList\Control\FilterControl;
use Presentation\Framework\Component\ManagedList\Control\PaginationControl;
use Presentation\Framework\Component\Text;
use Presentation\Framework\Input\InputOption;
use Presentation\Framework\Data\ArrayDataProvider;
use Presentation\Framework\Data\DbTableDataProvider;
use Presentation\Framework\Data\Operation\FilterOperation;
use Presentation\Framework\Input\InputSource;
use Presentation\Framework\Rendering\SimpleRenderer;
use Presentation\Grids\Column;
use Presentation\Grids\Component\PageTotalsRow;
use Presentation\Grids\Component\Row;
use Presentation\Grids\Component\SolidRow;
use Presentation\Grids\Control\ColumnSortingControl;
use Presentation\Grids\Control\ColumnSortingView;
use Presentation\Grids\Grid;

class Controller extends AbstractController
{
    protected function getUsersData()
    {
        return include(dirname(__DIR__) . '/fixtures/users.php');
    }

    protected function getRenderer()
    {
        return new SimpleRenderer([
            __DIR__ . '/resources/views',
            dirname(dirname(__DIR__)) . '/resources/views'
        ]);
    }

    protected function getDataProvider($operations = [])
    {
        return (isset($_GET['use-db']) && $_GET['use-db'])
            ? new DbTableDataProvider(
                db_connection(),
                'users',
                $operations
            )
            : new ArrayDataProvider(
                $this->getUsersData(),
                $operations
            );
    }

    public function index()
    {
        $out = '';
        $out .= $this->renderMenu();
        $out .= '<h1>Presentation/Grids Test Application</h1><h2>Index Page</h2>';

        return $out;
    }

    public function demo1()
    {
        $provider = $this->getDataProvider();
        $grid = new Grid($provider, [
            new Column('id'),
            new Column('name'),
            new Column('role'),
        ]);
        return $this->page($grid->render(), 'Basic Grid');
    }

    /**
     *
     *
     * @return string
     */
    public function demo2()
    {
        $provider = $this->getDataProvider();
        $grid = (new Grid)
            ->setDataProvider($provider)
            ->setColumns([
                new Column('id'),
                new Column('name'),
                new Column('role'),
            ]);
        $grid->components()->getControlRow()->addChildren([
            new FilterControl('name', FilterOperation::OPERATOR_EQ, new InputOption('name', $_GET)),
            new FilterControl('role', FilterOperation::OPERATOR_EQ, new InputOption('role', $_GET))
        ]);
        $grid->components()->getTableHeading()->addChild(
            (new SolidRow([new Text('additional row')]))->setSortPosition(2)
        );
        return $this->page($grid->render(), 'Filters');
    }

    public function demo3()
    {
        $provider = $this->getDataProvider();
        $grid = (new Grid())
            ->setDataProvider($provider)
            ->setColumns([
                new Column('id'),
                new Column('name'),
                new Column('role'),
            ]);
        $grid->compose('control_row_hider', 'row1', $row = new Row());
        $nameFilter = new FilterControl('name', FilterOperation::OPERATOR_EQ, new InputOption('name', $_GET));
        $roleFilter = new FilterControl('role', FilterOperation::OPERATOR_EQ, new InputOption('role', $_GET));
        $row->getCell('name')->addChild($nameFilter);
        $row->getCell('role')->addChild($roleFilter);

        $nameFilter->getView()->setLabel('');
        $roleFilter->getView()->setLabel('');

        return $this->page($grid->render(), 'Filters placed under column headers');
    }

    /**
     * Pagination
     *
     * @return string
     */
    public function demo4()
    {
        $provider = $this->getDataProvider();
        $grid = new Grid();
        $input = new InputSource($_GET);
        $grid
            ->setDataProvider($provider)
            ->setColumns([
                new Column('id'),
                new Column('name'),
                new Column('role'),
                new Column('birthday'),
            ]);
        $grid->components()->getControlRow()->addChildren([
            new FilterControl('role', FilterOperation::OPERATOR_EQ, $input->option('role')),
            new FilterControl('name', FilterOperation::OPERATOR_EQ, $input->option('name')),
            new FilterControl('birthday', FilterOperation::OPERATOR_EQ, $input->option('birthday')),
        ]);

        $p = new PaginationControl($input->option('page', 1), 5, $provider);
        $p->setSortPosition(2);
        $grid->components()->getContainer()->addChild($p);

        return $this->page($grid, 'Pagination');
    }

    /**
     * Totals
     *
     * @return string
     */
    public function demo5()
    {
        $provider = $this->getDataProvider();

        $input = new InputSource($_GET);
        $grid = new Grid($provider, [
            new Column('id'),
            new Column('name'),
            new Column('role'),
            new Column('birthday'),
        ]);
        $grid->components()->getControlRow()->addChildren([
            new FilterControl('role', FilterOperation::OPERATOR_EQ, $input->option('role')),
            new FilterControl('name', FilterOperation::OPERATOR_EQ, $input->option('name')),
            new FilterControl('birthday', FilterOperation::OPERATOR_EQ, $input->option('birthday')),
        ]);
        $grid->compose('container', 'pagination', new PaginationControl($input->option('page', 1), 5, $provider));
        $grid->components()->getTableFooter()->addChild(new PageTotalsRow());
        return $this->page($grid, 'Page Totals');
    }


    /**
     *
     * @return string
     */
    public function demo6()
    {
        $provider = $this->getDataProvider();
        $grid = new Grid($provider, [
            new Column('id'),
            new Column('name'),
            new Column('role'),
            new Column('birthday'),
        ]);
        $grid->components()->getTableFooter()->addChild(
            new SolidRow([new PaginationControl(new InputOption('p', $_GET, 1), 5, $provider)])
        );
        return $this->page($grid, 'Pagination inside table footer');
    }

    /**
     * @return string
     */
    public function demo7()
    {
        $provider = $this->getDataProvider();
        $grid = new Grid($provider, [
            new Column('id'),
            new Column('name'),
            $roleColumn = new Column('role'),
            new Column('birthday'),
        ]);

        foreach($grid->getColumns() as $column) {
            $column->getTitleCell()->addChild(
                new ColumnSortingControl(
                    $column->getName(),
                    new InputOption('sort', $_GET),
                    new ColumnSortingView($this->getRenderer())
                )
            );
        }

        $grid->components()->getTableFooter()->addChild(
            new SolidRow([new PaginationControl(new InputOption('p', $_GET, 1), 5, $provider)])
        );

        return $this->page($grid, 'column sorting');
    }
}