<?PHP



    include '../wikibot.classes.php';
    include 'utils.config.php';
    $wpi = new wikipediaindex();
    $diff = $wpi->diff('User:ClueBot_II/sandbox.js', 315968196, 379162064);
    $newraw = explode(' ', preg_replace('/\s+/', ' ', trim($diff[ 0 ])));
    $oldraw = explode(' ', preg_replace('/\s+/', ' ', trim($diff[ 1 ])));
    $new = array_diff($newraw, $oldraw);
    $old = array_diff($oldraw, $newraw);

    echo 'Removed: '.implode(', ', $old).' -- Added: '.implode(', ', $new)."\n";
