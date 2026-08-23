<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration(); ?> <?= $interfaces ?>
{
    <?= $traits ?>

    public function getId(): string
    {
        return '<?= $id; ?>';
    }
}
