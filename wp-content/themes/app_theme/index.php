<?php //get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php //the_title(); ?></h1>
            </header>
            <div class="entry-content">
                <?php
                echo "<br>nombre<br>";
                echo the_field('nombre', 21);
                echo "<br>email<br>";
                var_dump(the_field('email', 21));
                echo "<br>pass<br>";
                var_dump(the_field('pass', 21));
                echo "<br>dob<br>";
                var_dump(the_field('dob', 21));
                echo "<br>foto<br>";
                var_dump(the_field('foto', 21));
                echo "<br>data<br>";
                var_dump(the_field('data', 21));
                echo "<br>relation<br>";
                var_dump(get_field('relation', 21));
                echo "<br><br>";

                ?>
            </div>
        </article>
    </main>
</div>

<?php //get_footer(); ?>
