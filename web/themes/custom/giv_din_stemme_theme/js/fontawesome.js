/* // Add fontawesome icons */

// Import the svg core
const { library, dom } = require("@fortawesome/fontawesome-svg-core");

// To keep the package size as small as possible we only import icons we use
// Import the icons from the free solid package.
const {
  faBars,
  faMicrophoneLines,
  faCircleArrowRight,
  faMicrophone,
  faMicrophoneSlash,
  faArrowRightFromBracket,
  faStop,

  faCircleInfo,
  faTriangleExclamation,
} = require("@fortawesome/free-solid-svg-icons");

// Import icons from the free regular package
const {
  faCircleDot,
  faCircleCheck,
  faClock,
  faUser,
} = require("@fortawesome/free-regular-svg-icons");

// Also import CircleCheck from the free solid package
import { faCircleCheck as fasCircleCheck } from "@fortawesome/free-regular-svg-icons";

// Add the icons to the library for replacing <i class="fa-solid fa-sort"></i> with the intended svg.
library.add(
  faBars,
  faMicrophoneLines,
  faCircleDot,
  faCircleArrowRight,
  faCircleCheck,
  faMicrophone,
  faMicrophoneSlash,
  faClock,
  faUser,
  faArrowRightFromBracket,
  faStop,
  faCircleInfo,
  fasCircleCheck,
  faTriangleExclamation,
);

// Run <i> to <svg> replace
dom.i2svg();
