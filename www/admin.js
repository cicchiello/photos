"use strict";

function updateHideButtonState() {
    const hideButton = document.getElementById('hideImagesButton');
    if (hideButton) {
        hideButton.disabled = getCheckedSet().size === 0;
    }
}

function updateUnhideButtonState() {
    const unhideButton = document.getElementById('unhideImagesButton');
    if (unhideButton) {
        unhideButton.disabled = getCheckedSet().size === 0;
    }
}


async function handleHideImages() {
    var checkedList = Array.from(getCheckedSet());
    if (checkedList.length === 0) return;

    for (const imageId of checkedList) {
        try {
            const url = 'hide_image.php?imageid=' + imageId + '&csrf=' + encodeURIComponent(csrfToken);
            const response = await fetch(url);
            if (!response.ok) {
                console.error('Failed to hide image: ' + imageId);
            }
        } catch (error) {
            console.error('Error hiding image: ' + error);
        }
    }

    clearChecks();
    var f = document.getElementById("imgArrayFrame");
    f.contentWindow.location.reload();
}


async function handleUnhideImages() {
    var checkedList = Array.from(getCheckedSet());
    if (checkedList.length === 0) return;

    for (const imageId of checkedList) {
        try {
            const url = 'unhide_image.php?imageid=' + imageId + '&csrf=' + encodeURIComponent(csrfToken);
            const response = await fetch(url);
            if (!response.ok) {
                console.error('Failed to unhide image: ' + imageId);
            }
        } catch (error) {
            console.error('Error unhiding image: ' + error);
        }
    }

    clearChecks();
    var f = document.getElementById("imgArrayFrame");
    f.contentWindow.location.reload();
}
