import './bootstrap';
import Swal from 'sweetalert2';

// Import CSS bawaan
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

// Import library JS
import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

import './pos/pos';
import './sales/index';
import ROLE from './role';

// Swal 2
window.Swal = Swal;
// Register plugin
FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginImagePreview);

window.FilePond = FilePond;
window.ROLE = ROLE;

