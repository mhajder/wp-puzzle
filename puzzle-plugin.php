<?php
/*
Plugin Name: Wordpress Puzzle Plugin
Description: Include a puzzle in your blog with just one shortcode.
Version: 1.0
Author: Mateusz Hajder
Text Domain: wp-puzzle
*/

/**
 * Initialise plugin translations
 */
function wp_puzzle_init()
{
    load_plugin_textdomain('wp-puzzle', false, 'wp-puzzle/languages');
}

add_action('init', 'wp_puzzle_init');

/**
 * Default values for plugin settings
 *
 * @return string[]
 */
function get_puzzle_plugin_default_options(): array
{
    return array(
        'div_id' => 'puzzle',
        'width' => '1024',
        'height' => '768',
        'frame_color' => 'null',
        'frame_outerColor' => 'null',
        'text_color' => '#679b95',
        'image' => 'sample.jpg',
        'horizontal_pieces' => '6',
        'vertical_pieces' => '4',
        'align_center' => 'true',
        'label_text' => __('Puzzle - tap or shift tap to rotate', 'wp-puzzle'),
    );
}

/*
 * Registering a webhook when there is a shortcode on a given page.
 */
add_shortcode('puzzle', 'puzzle_plugin');

/**
 * The main function of the plugin
 *
 * @param $attributes
 * @return string
 */
function puzzle_plugin($attributes): string
{
    // Adding CreateJS to the footer of the page
    wp_register_script('wp-puzzle-createjs', plugins_url('/js/createjs_1.3.4.js', __FILE__));
    wp_enqueue_script('wp-puzzle-createjs');

    // Adding ZIM.js to the footer of the page
    wp_register_script('wp-puzzle-zim', plugins_url('/js/zim_cat04.js', __FILE__));
    wp_enqueue_script('wp-puzzle-zim');

    // Set the default plugin options
    $defaultOptions = get_puzzle_plugin_default_options();
    $div_id = $defaultOptions['div_id'];
    $width = $defaultOptions['width'];
    $height = $defaultOptions['height'];
    $frame_color = $defaultOptions['frame_color'];
    $frame_outerColor = $defaultOptions['frame_outerColor'];
    $text_color = $defaultOptions['text_color'];
    $image = $defaultOptions['image'];
    $horizontal_pieces = $defaultOptions['horizontal_pieces'];
    $vertical_pieces = $defaultOptions['vertical_pieces'];
    $align_center = $defaultOptions['align_center'];
    $label_text = $defaultOptions['label_text'];

    // Overwrite default plugin options with options from shortcode.
    extract(shortcode_atts(array(
        'div_id' => $div_id,
        'width' => $width,
        'height' => $height,
        'frame_color' => $frame_color,
        'frame_outerColor' => $frame_outerColor,
        'text_color' => $text_color,
        'image' => $image,
        'horizontal_pieces' => $horizontal_pieces,
        'vertical_pieces' => $vertical_pieces,
        'align_center' => $align_center,
        'label_text' => $label_text,
    ), $attributes));

    // Add css styles
    wp_register_style('wp-puzzle-style', plugins_url('/css/puzzle-plugin.css', __FILE__));
    wp_add_inline_style('wp-puzzle-style', '
    #' . $div_id . ' {
        position: relative;
        max-width: 100%;
        ' . ($align_center === 'true' ? 'text-align: center;' : '') . '
    }
    
    #' . $div_id . 'Canvas {
        max-width: 100%;
    }
    ');
    wp_enqueue_style('wp-puzzle-style');

    // Add javascript code
    wp_register_script('wp-puzzle-script', plugins_url('/js/puzzle-plugin.js', __FILE__));
    wp_add_inline_script('wp-puzzle-script', '
    // Code under the ZIM.js license
    
    // Wordpress plugin settings
    const settingsDivID = "' . $div_id . '";
    const settingsWidth = ' . $width . ';
    const settingsHeight = ' . $height . ';
    const settingsFrameColor = "' . $frame_color . '";
    const settingsFrameOuterColor = "' . $frame_outerColor . '";
    const settingsTextColor = "' . $text_color . '";
    const settingsImage = "' . $image . '"; // 800px X 600px
    const settingsHorizontalPieces = ' . $horizontal_pieces . ';
    const settingsVerticalPieces = ' . $vertical_pieces . ';
    const settingsLabelText = "' . $label_text . '";
    
    const frame = new Frame(settingsDivID, settingsWidth, settingsHeight, settingsFrameColor, settingsFrameOuterColor, settingsImage, "' . plugins_url('images', __FILE__) . '/");
    frame.on("ready", () => {
      const stage = frame.stage;
      let stageW = frame.width;
      let stageH = frame.height;
    
      // put your code here
    
      const mob = mobile();
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // CLASS to make JigSaw piece with bumps out or in or no bump on any four edges
      class Piece extends Shape {
        // format is 1 for bump out, -1 for bump in and 0 for no bump
        constructor(w = 100, h = 100, format = [1, 1, 1, 1], s = black, ss = 4, f = white) {
          super(w, h);
          const p = Piece.part; // static property - defined below class
          const g = Piece.gap;
          this.s(s).ss(ss).f(f).mt(0, 0);
          if (format[0] == 0) this.lt(w, 0); // top left-right
          else {
            this.lt(w * p, 0);
            let s = format[0] == 1 ? -1 : 1; // sign                
            this.ct(w * (p - g / 2), s * w * g, w / 2, s * w * g); // curve left to middle
            this.ct(w * (p + g + g / 2), s * w * g, w * (1 - p), 0); // curve middle to right           
            this.lt(w, 0)
          }
          if (format[1] == 0) this.lt(w, h); // right top-bottom
          else {
            this.lt(w, h * p);
            let s = format[1] == 1 ? 1 : -1;
            this.ct(w + s * w * g, h * (p - g / 2), w + s * w * g, h / 2);
            this.ct(w + s * w * g, h * (p + g + g / 2), w, h * (1 - p));
            this.lt(w, h)
          }
          if (format[2] == 0) this.lt(0, h); // bottom right-left
          else {
            this.lt(w * (1 - p), h);
            let s = format[2] == 1 ? 1 : -1;
            this.ct(w * (p + g + g / 2), h + s * w * g, w / 2, h + s * w * g);
            this.ct(w * (p - g / 2), h + s * w * g, w * p, h + 0);
            this.lt(0, h)
          }
          if (format[3] == 0) this.lt(0, 0); // left bottom-top
          else {
            this.lt(0, h * (1 - p));
            let s = format[3] == 1 ? -1 : 1;
            this.ct(s * w * g, h * (p + g + g / 2), s * w * g, h / 2);
            this.ct(s * w * g, h * (p - g / 2), 0, h * p);
            this.lt(0, 0)
          }
          this.cp(); // close path
        }
      }
    
      Piece.part = .37; // part of the edge with no gap ratio
      Piece.gap = 1 - Piece.part * 2; // gap ratio of edge
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // PUZZLE SIZE
    
      let numX = settingsHorizontalPieces;
      let numY = settingsVerticalPieces;
      const obj = getQueryString(); // note, getQueryString returns {} now if no query string    
      if (obj.col) numX = Math.min(14, Number(obj.col)); // or we would have to start caching things...
      if (obj.row) numY = Math.min(10, Number(obj.row));
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // PICTURE
    
      const pic = asset(settingsImage).clone().center().alp(.3).vis(false); // checkbox later to turn on
      const w = pic.width / numX;
      const h = pic.height / numY;
    
      // chop the picture into bitmaps
      // false is for an array rather than the default Tile output 
      // extra is because bumps go outside the width and height
      const extra = Math.max(w, h) * Piece.gap;
      const pics = chop(asset(settingsImage), numX, numY, false, extra);
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // PIECES
    
      // makePieces gets called from Tile - for each piece
      let count = 0;
      let lastX = rand() > .5 ? 1 : -1; // 1 or -1 for out or in horizontally
      let lastYs = []; // 1 or -1 vertically - remember with array and modulus
      loop(numX, i => {
        lastYs.push(rand() > .5 ? 1 : -1);
      });
    
      function makePiece() {
    
        // prepare format for jigsaw piece [1,0,-1,0] 
        // 1 bump out, 0 no bump, -1 bump in, etc.
        let currentX = lastX * -1; // opposite of last x
        let currentY = lastYs[count % numX] * -1; // opposite of last y
        let nextX = rand() > .5 ? 1 : -1; // randomize the next 1 or -1 for out or in horizontally
        let nextY = rand() > .5 ? 1 : -1; // and vertically
        // top, right, bottom, left
        let format = [currentY, nextX, nextY, currentX];
        lastX = nextX;
        lastYs[count % numX] = nextY;
    
        // override edges to 0
        if (count < numX) format[0] = 0;
        else if (count >= numX * numY - numX) format[2] = 0;
        if (count % numX == 0) format[3] = 0;
        else if ((count - numX + 1) % numX == 0) format[1] = 0;
    
        // make a container to hold jigsaw shape and later picture part
        let piece = new Container(w, h).centerReg({add: false});
        piece.puzzle = new Piece(w, h, format).addTo(piece);
        piece.mouseChildren = false;
        count++;
        return piece;
      }
    
      const pieces = new Tile({
        obj: makePiece,
        cols: numX,
        rows: numY,
        clone: false // otherwise makes clone of piece
      })
        .center()
        .drag(stage).animate({
          props: {alpha: 1},
          time: .1,
          sequence: .05
        });
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // HINT AND SNAP HIT BOX
    
      // tidy up alpha setting on hint around border
      const outline = new Rectangle(pic.width, pic.height, clear, mist, 4).center().ord(-1) // under pieces    
      const hint = pieces.clone(true) // exact
        .center()
        .ord(-1) // under pieces     
        .cache(-5, -5, pic.width + 10, pic.height + 10) // cache by default does not include outside border 
        .alp(.2)
        .vis(0); // checkbox below to show
    
      // make a little box to do hit test to see if in right place
      const snap = 50; // pixel distance considered correct
      loop(hint, h => {
        h.box = new Rectangle(snap, snap).centerReg(h).vis(0); // do not use alpha=0 as that will make it not hittable        
      });
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // ADD PICTURE TO PIECES, ADD EVENTS, ROTATE AND SCRAMBLE
    
      const padding = 50;
      const rotate = true;
      loop(pieces, (piece, i) => {
        piece.alp(0); // sequence animation above will animate in alpha
        pics[i].addTo(piece).setMask(piece.puzzle);
        // test on mobile and see if you need to cache...
        // usually this is just cache() but the bumps are outside the piece 
        // and the cache size really does not make a difference if rest is background transparent 
        if (mob) piece.cache(-100, -100, piece.width + 200, piece.width + 200);
        if (rotate) {
          piece.rotation = shuffle([0, 90, 180, 270])[0];
          piece.tap({
            time: .5, // within .5 seconds
            call: () => {
              pieces.noMouse(); // do not let anything happen while animating until done
              piece.animate({
                props: {rotation: String(frame.shiftKey ? -90 : 90)}, // string makes relative
                time: .2,
                call: () => {
                  pieces.mouse();
                  test(piece);
                }
              });
              stage.update();
            },
            call2: () => { // if no tap
              test(piece);
            }
          });
        } else {
          piece.on("pressup", () => {
            test(piece);
          });
        }
        piece.on("pressdown", () => {
          // shadows are expensive on mobile
          // could add it to container so shadow inside container 
          // then cache the container but might not be worth it
          if (!mob) piece.sha("rgba(0,0,0,.4)", 5, 5, 5);
        });
    
        // scramble location     
        piece.loc(padding + w / 2 + rand(stageW - w - padding * 2) - pieces.x, padding + h / 2 + rand(stageH - h - padding * 2) - pieces.y);
      });
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // EMITTER
    
      const emitter = new Emitter({
        obj: new Poly({min: 40, max: 70}, [5, 6], .5, [orange, blue, green]),
        num: 2,
        force: 6,
        startPaused: true
      });
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // MESSAGE LABEL
    
      const num = numX * numY;
      let placed = 0;
      STYLE = {color: settingsTextColor, size: 24}
      const stats = new Label({
        text: `' . __('Placed ${placed} pieces of ${num}', 'wp-puzzle') . '`,
        italic: true,
        align: CENTER
      }).centerReg().pos(0, 20, CENTER, BOTTOM);
    
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // TEST FOR PIECE IN RIGHT PLACE AND END 
    
      function test(piece) {
        piece.sha(-1);
        let box = hint.items[piece.tileNum].box;
        if (piece.rotation % 360 == 0 && box.hitTestReg(piece)) {
          piece.loc(box).bot().noMouse();
          emitter.loc(box).spurt(30);
          placed++;
          if (placed == num) {
            stats.text = `' . __('Congratulations all ${num} pieces placed!', 'wp-puzzle') . '`;
            timeout(1, function () {
              emitter.emitterForce = 8;
              emitter.center().mov(0, -170).spurt(100)
            })
            timeout(2, function () {
              hintCheck.removeFrom();
              picCheck.removeFrom();
              picCheck.checked = true;
              pieces.animate({alpha: 0}, .7);
              outline.animate({alpha: 0}, .7);
              hint.animate({alpha: 0}, .7);
              pic.alp(0).animate({alpha: 1}, .7);
              new Button({
                label: "' . __('AGAIN', 'wp-puzzle') . '",
                color: white,
                corner: [60, 0, 60, 0],
                backgroundColor: settingsTextColor,
                rollBackgroundColor: blue
              })
                .sca(.5)
                .pos(150, 20, LEFT, BOTTOM)
                .alp(0)
                .animate({alpha: 1})
                .tap(() => {
                  zgo(window.location.href) // Refresh page
                })
            });
          } else stats.text = `' . __('Placed ${placed} piece${placed == 1 ? "" : "s"} of ${num}', 'wp-puzzle') . '`;
        } else stage.update();
      }
    
      // ~~~~~~~~~~~~~~~~~~~~~
      // CHECKBOXES AND FINISHING TOUCHES
    
      Style.addType("CheckBox", {borderColor: settingsTextColor});
      const hintCheck = new CheckBox(30, "' . __('hint', 'wp-puzzle') . '").alp(.8).pos(50, 35, LEFT, BOTTOM).wire({
        target: hint,
        prop: "visible",
        input: "checked"
      });
      const picCheck = new CheckBox(30, "' . __('picture', 'wp-puzzle') . '").alp(.8).pos(50, 0, LEFT, BOTTOM).wire({
        target: pic,
        prop: "visible",
        input: "checked"
      });
    
      new Label(settingsLabelText).pos(0, 30, CENTER);
    
      pieces.top(); // add pieces above everything
    
      stage.update(); // needed to view changes
    });
    ');
    wp_enqueue_script('wp-puzzle-script');

    // Return div with ID to make puzzles appear in the given place
    return '<div id="' . $div_id . '"></div>';
}
