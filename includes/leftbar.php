<?php require_once __DIR__ . '/auth.php'; sisatRequireLogin(); $rolActual = sisatRoleId(); ?>
<div class="left-sidebar box-shadow" style="
    background-color: #0c4d58ff;
    padding-top: 30px;
">
    <div class="sidebar-content">

        <div class="user-info">
            <img src="assets/images/avatar.jfif"
                 alt="Profile"
                 class="img-circle profile-img"
                 width="40px">

            <p style="color: white;">
                Sistemas de Inventario <br> Serviucis
            </p>
            <br>
        </div>

        <div class="sidebar-nav">
            <ul class="side-nav color-gray">

                <li class="nav-header">
                    <span>SERVIUCIS</span>
                </li>

                <li>
                    <a href="dashboard.php">
                        <i class="fa fa-dashboard"></i>
                        <span>Inicio</span>
                    </a>
                </li>

                <li class="nav-header">
                    <span>MENU DEL SISTEMA</span>
                </li>

                <?php if ($rolActual === 1): ?>
                <li class="has-children">
                    <a href="#"><i class="fa fa-users"></i><span>Gestión de Usuarios</span><i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="create-usuario.php"><i class="fa fa-plus"></i><span>Registrar Usuarios</span></a></li>
                        <li><a href="manage-usuarios.php"><i class="fa fa-bars"></i><span>Ver Usuarios</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (in_array($rolActual, [1,2], true)): ?>
                <li class="has-children">
                    <a href="#"><i class="fa fa-laptop"></i><span>Gestión de Activos</span><i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="create-activo.php"><i class="fa fa-plus"></i><span>Registrar Activo</span></a></li>
                        <li><a href="manage-activos.php"><i class="fa fa-bars"></i><span>Ver Activos</span></a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li><a href="manage-activos.php"><i class="fa fa-laptop"></i><span>Consultar Inventario</span></a></li>
                <?php endif; ?>

                <li class="has-children">
                    <a href="#"><i class="fa fa-exchange"></i><span>Gestión de Traslados</span><i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <?php if ($rolActual === 1): ?>
                        <li><a href="registrar-traslado.php"><i class="fa fa-plus"></i><span>Registrar Traslado</span></a></li>
                        <li><a href="manage-solicitudes-traslado.php"><i class="fa fa-bell"></i><span>Solicitudes pendientes</span></a></li>
                        <?php elseif ($rolActual === 2): ?>
                        <li><a href="solicitar-traslado.php"><i class="fa fa-paper-plane"></i><span>Solicitar Traslado</span></a></li>
                        <?php endif; ?>
                        <li><a href="manage-traslados.php"><i class="fa fa-history"></i><span>Historial de Traslados</span></a></li>
                    </ul>
                </li>

                <li class="has-children">
                    <a href="#"><i class="fa fa-users"></i><span>Gestión Asignaciones</span><i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <?php if (in_array($rolActual, [1,2], true)): ?><li><a href="add-asignacion.php"><i class="fa fa-plus"></i><span>Agregar Asignación</span></a></li><?php endif; ?>
                        <li><a href="manage-asignacion.php"><i class="fa fa-bars"></i><span>Ver Asignaciones</span></a></li>
                    </ul>
                </li>

                <li class="has-children">
                    <a href="#"><i class="fa fa-wrench"></i><span>Mantenimientos</span><i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <?php if (in_array($rolActual, [1,2], true)): ?><li><a href="add-mantenimiento.php"><i class="fa fa-plus"></i><span>Registrar mantenimiento / reparación</span></a></li><li><a href="programar-masivo.php"><i class="fa fa-calendar"></i><span>Programar jornadas</span></a></li><?php endif; ?>
                        <li><a href="manage-mantenimientos.php"><i class="fa fa-list"></i><span>Consultar mantenimientos</span></a></li>
                    </ul>
                </li>

                <li>
                    <a href="change-password.php">
                        <i class="fa fa-key"></i>
                        <span>Cambio de Clave</span>
                    </a>
                </li>

                <li>
                    <a href="author.php">
                        <i class="fa fa-gear"></i>
                        <span>Ayuda</span>
                    </a>
                </li>

            </ul>
        </div>

    </div>
</div>