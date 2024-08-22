/* // Add fontawesome icons */

// Import the svg core
const { library, dom } = require("@fortawesome/fontawesome-svg-core");

// To keep the package size as small as possible we only import icons we use
// Import the icons from the free solid package.
const {
  faBars,
  faRobot,
  faXmark,
  faImage,
  faLanguage,
  faVideoCamera,
  faMicrophoneLines,
  faWindowMinimize,
  faWindowMaximize,
  faWindowClose,
  faRotateLeft,
  faMessage,
  faCircle,
  faAngleRight,
  faCircleArrowRight,
  faMicrophone,
  faMicrophoneSlash,
  faArrowRightFromBracket,
} = require("@fortawesome/free-solid-svg-icons");

// Import icons from the free regular package
const {
  faCircleCheck,
  faClock,
  faUser,
} = require("@fortawesome/free-regular-svg-icons");

// Add the icons to the library for replacing <i class="fa-solid fa-sort"></i> with the intended svg.
library.add(
  // Solid
  faBars,
  faRobot,
  faXmark,
  faImage,
  faLanguage,
  faVideoCamera,
  faMicrophoneLines,
  faWindowMinimize,
  faWindowMaximize,
  faWindowClose,
  faRotateLeft,
  faMessage,
  faCircle,
  faAngleRight,
  faCircleArrowRight,
  faCircleCheck,
  faMicrophone,
  faMicrophoneSlash,
  faClock,
  faUser,
  faArrowRightFromBracket,
  // Brand
  // faXTwitter
);

// Run <i> to <svg> replace
dom.i2svg();
