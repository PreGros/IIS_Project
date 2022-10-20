// loads the jquery package from node_modules
import $ from 'jquery';

document.addEventListener('ready', () => {
    document.querySelector('#myTable').insertAdjacentText('beforebegin', '<h1>here</h1>');
});
