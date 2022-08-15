(function () {
  'use strict';

  import './src/example_filtered_multiple.scss';
  import example_filtered_multiple from './dist/example_filtered_multiple';

  var element = Object.create(HTMLElement.prototype);
  element.createdCallback = function () {};
  element.attachedCallback = function () {};
  element.detachedCallback = function () {};
  element.attributeChangedCallback = function (attr, oldVal, newVal) {};
  document.registerElement('example_filtered_multiple', {
    prototype: element
  });
}());
