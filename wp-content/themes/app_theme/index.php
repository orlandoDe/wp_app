<?php 
// get_header(); 

acf_form_head();
$id = $_GET['id'];
// var_dump($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Form</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- <script type='text/javascript' src='wp-content/plugins/advanced-custom-fields/assets/build/js/acf.js'></script> -->
    <!-- <script type='text/javascript' src='wp-content/plugins/advanced-custom-fields/assets/build/js/acf-internal-post-type.js'></script> -->
    <!-- <script type='text/javascript' src='wp-content/plugins/advanced-custom-fields/assets/build/js/acf-input.js'></script> -->
</head>
<body>
    <label>App</label>

<!-- LOGIN -->
<?php 
// Usage
// $trainer_id = 21;
// $trainer = fetch_trainer_by_id($trainer_id);
// echo '<pre>';
// print_r($trainer);
// echo '</pre>';

?>



<form>
    <input type="text" name="email" id="email" placeholder="Email" />
    <input type="text" name="pass" id="pass" placeholder="Contraseña" />
    <input type="button" id="loginBtn" name="Login" value="Login">
</form>

<!-- fin LOGIN -->




    <form method="get" action="" style="display: none">
        <label for="idInput">Trainer ID:</label>
        <input type="text" id="idInput" name="id" required>
        <button type="submit">Submit</button>
    </form>
<br>
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php //the_title(); ?></h1>
                </header>
                <input type="button" id="datosTrainer" class="buttonAction" data-toggle="0" data-hijo="datos" value="Datos Trainer" />
                <input type="button" id="datosAlumnos" class="buttonAction" data-toggle="0" data-hijo="alumnos" value="Alumnos" />
                <input type="button" id="agenda" class="buttonAction" data-toggle="0" data-hijo="calendario" value="Agenda" />
                <div id="datos" data-id="<?php echo wp_create_nonce('wp_rest');?>" class="entry-content" style="display:none;">
                    <label><strong>Perfil</strong></label>
                    <?php
                    echo "<br>nonce<br>";
                    echo wp_create_nonce('wp_rest');
                    echo "<br>ID<br>";
                    echo $id;
                    echo "<br>nombre<br>";
                    echo the_field('nombre', $id);
                    echo "<br>email<br>";
                    echo the_field('email', $id);
                    // echo "<br>pass<br>";
                    // var_dump(the_field('pass', $id));
                    // echo "<br>dob<br>";
                    // var_dump(the_field('dob', $id));
                    // echo "<br>foto<br>";
                    // var_dump(the_field('foto', $id));
                    // echo "<br>data<br>";
                    // var_dump(the_field('data', $id));
                    echo "<br>Agenda<br>";
                    $alumnos = get_field('relation', $id);
                    // var_dump($alumnos);
                    echo "<br><br>";
                    ?>
                    
                </div>
                
                <div id="alumnos" style="display: none">
                    <label><strong>Alumnos</strong></label> <br>
                    <?php
                    $idsCalendario = array();
                    $idsCalendarioTodos = array();
                    foreach ($alumnos as $alumn) {
                        // var_dump($alumn->ID);
                        $idAlumno = $alumn->ID;
                        echo $id."@".$idAlumno;
                        array_push($idsCalendario, $id."@".$idAlumno);
                        echo "<br>";
                        // // echo the_field('nombre', $idAlumno);
                        // echo "<br>";
                        // // echo the_field('email', $idAlumno);
                        // echo "<br>";
                        // // var_dump(the_field('data', $idAlumno));
                        // echo "<br>";
                    }
                   
                    echo "<br><br>";
                    // var_dump($idsCalendario);
                    foreach ($idsCalendario as $calendarioAlumno) {
                        $args = array(
                            'post_type'      => 'calendario',
                            'posts_per_page' => -1,
                            's'              => $calendarioAlumno,  // Search term
                            'post_title'           => $calendarioAlumno,  // Exact match for post name
                            // 'meta_query'     => array(
                            //     'relation' => 'OR',
                            //     array(
                            //         'key'     => '_wp_page_template',  // Custom meta key if needed
                            //         'value'   => 'template-name.php',   // Custom value if needed
                            //         'compare' => '='
                            //     )
                            // )
                        );

                        $query = new WP_Query($args);
                        // var_dump($query->have_posts());
                        if ($query->have_posts()) {
                            while ($query->have_posts()) {
                                $query->the_post();
                                $representanteCalendario = get_field('alumno', get_the_ID());
                                // var_dump($representanteCalendario->ID);
                                $idsCalendarioTodos[]=["calendario"=>get_the_ID(),"Alumno"=>$representanteCalendario->ID];
                                // var_dump(get_the_ID());  // Get the ID of the current post
                                // Your loop content here
                            }
                            wp_reset_postdata();
                        } else {
                            // No posts found
                        }
                    }
                    ?>
                </div>
                <div id="calendario" style="display: none">
                    <input type="button" id="agendar" class="buttonAction" data-toggle="0" data-hijo="agendarForm" value="Agendar" /><br>
                    <label><strong>Agenda</strong></label><br>
                    <?php 
                    foreach ($idsCalendarioTodos as $fechas) {
                        echo "<br>ID Calendario<br>";
                        var_dump($fechas['calendario']);
                        echo "<br>data<br>";
                        echo the_field('data', $fechas['calendario']);
                        echo "<br><input type='text' data-idCalendario='".$fechas['calendario']."' id='alumno-".get_field('alumno', $fechas['calendario'])->ID."' value='".get_field('data', $fechas['calendario'])."' />";
                        echo "<br>";
                    }
                    ?>
                    <br>
                    <br>
                    <form id="agendarForm" class="agendar" method="get" action="" style="display:none;">
                        <label>ID Trainer</label><br>
                        <input type="text" name="idTrainer" id="idTrainer" value="<?php echo $_GET['id']; ?>" />
                        <label for="idInput">Agendar:</label><br>
                        <label>Alumno:</label>
                        <select name="alumnoSelect" id="alumnoSelect">
                            <?php  
                            echo "<option value='0'>Seleccionar</option>";
                            foreach ($alumnos as $alumn) {
                                $idAlumno = $alumn->ID;
                                echo '<option value="'.$idAlumno.'">'.get_field('nombre', $idAlumno).'</option>';
                            }
                            ?>
                            
                        </select>
                        
                        <label for="idInput">Tipo:</label>
                        <select name="servicio" id="servicio">
                            <option value="0">Seleccionar</option>
                            <option value="1">Clase</option>
                        </select>
                        <label for="datepicker">Fecha:</label>
                        <input type="text" id="datepicker" name="date">
                        <label for="datepicker">Hora:</label>
                        <div id="timeInputs">
                            <label for="startTime">Start Time:</label>
                            <input type="time" id="startTime" name="startTime">

                            <label for="endTime">End Time:</label>
                            <input type="time" id="endTime" name="endTime">
                        </div>
                        <button type="submit">Submit</button>
                    </form>
                </div>
                
            </article>
        </main>
    </div>
    <br>

    <script>
        //button toggler
        $( ".buttonAction" ).click(function() {    
        var hijo = $(this).attr('data-hijo'); 
            if($(this).attr('data-toggle')==0){
                
                $('#'+hijo).show(); 
                $(this).attr('data-toggle', 1);
            }else{
                $('#'+hijo).hide();
                $(this).attr('data-toggle', 0);
            }
        });
        // Get the ID from the URL
        const myId = new URLSearchParams(window.location.search).get('id');

        // Check if the ID exists and is not empty
        if (myId) {
            // Show the div using jQuery
            // $('#datos').show();
            // $('.agenda').show();
            // $('.agendar').show();
        }

        $("#alumnoSelect").change(function(){
            var id = $(this).val();
            var idCalendario = $("#alumno-"+id).attr('data-idCalendario');
            console.log($("#alumno-"+id).val());
            console.log(idCalendario);
            // if ($("#alumno-"+id).val()!=undefined) {
            //     jQuery.ajax({
            //         url: 'http://localhost/wp-json/wp/v2/calendario/'+idCalendario,
            //         type: 'DELETE',
            //         data: "",
            //         beforeSend: function(xhr) {
            //             xhr.setRequestHeader('X-WP-Nonce', $("#datos").attr("data-id")); // You need to replace Your_Wp_Nonce with the nonce for authentication
            //         },
            //         success: function(response) {
            //             console.log('Post created successfully:', response);
            //         },
            //         error: function(error) {
            //             console.error('Error creating post:', error);
            //         },
            //     });
            // }
        });

        $("#agendarForm").submit(function(e){
            e.preventDefault();
            var id = $("#alumnoSelect").val();
            var idCalendario = $("#alumno-"+id).attr('data-idCalendario');
            var postData = {
                title: $("#idTrainer").val()+"@"+$("#alumnoSelect").val(),
                acf: {trainer: $("#idTrainer").val(),
                      alumno: $("#alumnoSelect").val(),
                      data: '{"fecha":"'+$("#datepicker").val()+'","hora":"'+$("#startTime").val()+'-'+$("#endTime").val()+'","idAlumno:","'+$("#alumnoSelect").val()+'","servicio":"'+$("#servicio").val()+'"}'
                  },
                status: "publish",
            };
            if ($("#alumno-"+id).val()!=undefined) {
                console.log("update");
                console.log(idCalendario);
                jQuery.ajax({
                    url: 'http://localhost/wp-json/wp/v2/calendario/'+idCalendario,
                    type: 'POST',
                    data: postData,
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', $("#datos").attr("data-id")); // You need to replace Your_Wp_Nonce with the nonce for authentication
                    },
                    success: function(response) {
                        console.log('Post created successfully:', response);
                    },
                    error: function(error) {
                        console.error('Error creating post:', error);
                    },
                });
            }else{
                // console.log($("#agendarForm").serialize());
                // Make the API request
                jQuery.ajax({
                    url: 'http://localhost/wp-json/wp/v2/calendario/',
                    type: 'POST',
                    data: postData,
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', $("#datos").attr("data-id")); // You need to replace Your_Wp_Nonce with the nonce for authentication
                    },
                    success: function(response) {
                        console.log('Post created successfully:', response);
                    },
                    error: function(error) {
                        console.error('Error creating post:', error);
                    },
                });   
            }
            
            
        });



        $("#loginBtn").click(function(){
            $.ajax({
                type: 'POST',
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',  // WordPress AJAX URL
                data: {
                    action: 'create_trainer',  // The WordPress action to trigger
                    email: $("#email").val(),
                    pass: $("#pass").val(),
                    // Other data you want to send
                },
                success: function(response) {
                    console.log(response[0]);
                    console.log(response[1]);
                    // Handle the response
                }
            });
        })
    </script>
</body>
</html>



<?php //get_footer(); ?>
