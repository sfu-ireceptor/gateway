function result = histogram(inFile, varName, nBins, titleName, outFile, r, g, b)
    % inFile: The input file name
    % varName: The variable name of the column we are looking for
    % nBins: The numebr of bins in the histogram, nbins = -1 implies nBins calculated based on data
    % titleName: The title of the graph
    % outFile: The output file to use.

    % Our delimeters are the comma character, we expect 1 header line
    delimeterIn = ',';
    headerLinesIn = 1;

    % Read in the data file with the delimeter and header lines defined
    A = importdata(inFile, delimeterIn, headerLinesIn);

    % Extract the headers for the columns
    headers = A.colheaders;
    % disp(headers);
    % disp(size(A.colheaders{1}));

    % Search over the headers to find the variable we want to plot.
    % Note we strip off whitespace on both the data in the file and 
    % the variable provided.
    varIndex = -1;
    for k = 1:length(headers)
        % disp(headers(k));
        if strcmp(strtrim(headers(k)),strtrim(varName))
            varIndex = k;
        end
    end
    % fprintf(1, 'varIndex = %d\n', varIndex);
    % display(A.colheaders);
    % For text sorting and bin counting see here:
    %
    % http://www.mathworks.com/matlabcentral/answers/27263-counting-strings-in-cell-array-is-there-a-faster-solution

    % Generate the histogram if we found the variable.
    if varIndex > 0

        % Create a figure
        figure

        % If nbins < 0 caclulate the number of bins to use based on the data.
        if nBins < 0
	    % Get the unique arrays of data in the data. uniqueTableVal is an
	    % array with each element in the array listing the elements that
	    % are found in the raw data. Thus the first element is the minimum
	    % value found in the data and the last element is the maximum value
	    % found in the data. So our nBins is the difference.
	    [uniqueTableVal, ua, ub] = unique(A.data(:,varIndex));
	    lenUniqueVals = size(uniqueTableVal);
            maxValue = uniqueTableVal(lenUniqueVals(1));
            minValue = uniqueTableVal(1);
            nBins = maxValue - minValue;
	    % fprintf(1, 'Number of Bins calculated = %d %d,%d\n', nBins, maxValue, minValue);
	end

        % Create the histogram with the correct number of bins.
        hist(A.data(:,varIndex),nBins);
        set(get(gca,'child'),'FaceColor',[r g b],'EdgeColor','w');

        % Set the X axis label to be the variable name
        set(get(gca, 'XLabel'), 'String', varName);

        % Set the title of the histogram
        title(titleName);

        % Output the figure into the output file.
        print('-djpeg', outFile)

        % Return a success code.
    else
        fprintf(1, 'Histogram: Unable to find variable %s in file %s\n', varName, inFile);
    end

    % Return the result, -1 if error, positive integer if successful.
    result = varIndex;

end
